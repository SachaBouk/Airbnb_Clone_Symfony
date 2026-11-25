<?php

namespace App\Service;

use App\Entity\Amenity;
use App\Entity\Listing;
use App\Entity\User;
use App\Model\ImportReport;
use App\Model\ListingImportHost;
use App\Model\ListingImportRow;
use App\Repository\AmenityRepository;
use App\Repository\ListingRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Imports listings from a JSON Lines file: one JSON object per line, read line by line
 * so that big files do not have to fit in memory.
 *
 * Avoids duplicates: hosts are matched by email, amenities by slug, and a listing is
 * skipped when one with the same title, city and host already exists.
 */
final class ListingImporter
{
    private const BATCH_SIZE = 50;

    /** @var array<string, User> hosts already found or created during this import, by email */
    private array $hosts = [];

    /** @var array<string, Amenity> amenities already found or created during this import, by slug */
    private array $amenities = [];

    /** @var array<string, true> listings imported during this import (title|city|email) */
    private array $importedKeys = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly AmenityRepository $amenityRepository,
        private readonly ListingRepository $listingRepository,
        private readonly DenormalizerInterface $denormalizer,
        private readonly ValidatorInterface $validator,
        private readonly SluggerInterface $slugger,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @param callable(): void|null $onLineProcessed called after each line (e.g. to advance a progress bar)
     *
     * @throws \InvalidArgumentException when the file cannot be read
     */
    public function importFile(string $path, ?callable $onLineProcessed = null): ImportReport
    {
        $file = is_file($path) && is_readable($path) ? fopen($path, 'r') : false;
        if (false === $file) {
            throw new \InvalidArgumentException(\sprintf('Impossible de lire le fichier "%s".', $path));
        }

        $this->hosts = $this->amenities = $this->importedKeys = [];
        $report = new ImportReport();
        $lineNumber = 0;

        try {
            while (false !== ($line = fgets($file))) {
                ++$lineNumber;
                if ('' === trim($line)) {
                    continue;
                }

                $this->importLine($line, $lineNumber, $report);

                if (0 === $lineNumber % self::BATCH_SIZE) {
                    $this->em->flush();
                }
                if (null !== $onLineProcessed) {
                    $onLineProcessed();
                }
            }
        } finally {
            fclose($file);
        }

        $this->em->flush();

        return $report;
    }

    private function importLine(string $line, int $lineNumber, ImportReport $report): void
    {
        $row = $this->parse($line, $lineNumber, $report);
        if (null === $row) {
            return;
        }

        $key = mb_strtolower($row->title.'|'.$row->city.'|'.$row->host->email);
        if (isset($this->importedKeys[$key])) {
            ++$report->skipped;

            return;
        }

        $host = $this->findOrCreateHost($row->host, $report);
        $alreadyInDatabase = null !== $host->getId()
            && null !== $this->listingRepository->findOneBy(['title' => $row->title, 'city' => $row->city, 'host' => $host]);
        $this->importedKeys[$key] = true;

        if ($alreadyInDatabase) {
            ++$report->skipped;

            return;
        }

        $listing = (new Listing())
            ->setTitle($row->title)
            ->setDescription($row->description)
            ->setPriceByNight(number_format($row->priceByNight, 2, '.', ''))
            ->setCity($row->city)
            ->setCountry($row->country)
            ->setMaxGuests($row->maxGuests)
            ->setHost($host);

        foreach ($row->amenities as $amenityName) {
            $listing->addAmenity($this->findOrCreateAmenity($amenityName, $report));
        }

        $this->em->persist($listing);
        ++$report->created;
    }

    /**
     * Decodes and validates one line. Records the error and returns null when the line is invalid.
     */
    private function parse(string $line, int $lineNumber, ImportReport $report): ?ListingImportRow
    {
        try {
            $data = json_decode($line, true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $report->addError($lineNumber, 'JSON invalide.');

            return null;
        }

        if (!\is_array($data)) {
            $report->addError($lineNumber, 'Un objet JSON est attendu.');

            return null;
        }

        try {
            // "json" format: like #[MapRequestPayload], accepts an integer where a float is expected (185 → 185.0).
            $row = $this->denormalizer->denormalize($data, ListingImportRow::class, 'json');
        } catch (SerializerException $e) {
            $report->addError($lineNumber, 'Format invalide : '.$e->getMessage());

            return null;
        }

        $violations = $this->validator->validate($row);
        if (\count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[] = $violation->getPropertyPath().' : '.$violation->getMessage();
            }
            $report->addError($lineNumber, implode(' ; ', $messages));

            return null;
        }

        return $row;
    }

    private function findOrCreateHost(ListingImportHost $data, ImportReport $report): User
    {
        $email = mb_strtolower($data->email);
        if (isset($this->hosts[$email])) {
            return $this->hosts[$email];
        }

        $host = $this->userRepository->findOneBy(['email' => $email]);
        if (null === $host) {
            $host = (new User())
                ->setEmail($email)
                ->setFirstName($data->firstName)
                ->setLastName($data->lastName);
            // Imported hosts get a random password: nobody can log in with it until it is reset.
            $host->setPassword($this->passwordHasher->hashPassword($host, bin2hex(random_bytes(16))));
            $this->em->persist($host);
            ++$report->createdHosts;
        }

        return $this->hosts[$email] = $host;
    }

    private function findOrCreateAmenity(string $name, ImportReport $report): Amenity
    {
        $slug = $this->slugger->slug($name)->lower()->toString();
        if (isset($this->amenities[$slug])) {
            return $this->amenities[$slug];
        }

        // By name first: existing slugs do not always derive from the name (e.g. "Wi-Fi" → "wifi").
        $amenity = $this->amenityRepository->findOneBy(['name' => $name])
            ?? $this->amenityRepository->findOneBy(['slug' => $slug]);
        if (null === $amenity) {
            $amenity = (new Amenity())->setName($name)->setSlug($slug);
            $this->em->persist($amenity);
            ++$report->createdAmenities;
        }

        return $this->amenities[$slug] = $amenity;
    }
}
