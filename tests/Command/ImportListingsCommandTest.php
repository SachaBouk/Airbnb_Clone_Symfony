<?php

namespace App\Tests\Command;

use App\Entity\Amenity;
use App\Entity\Listing;
use App\Entity\User;
use App\Tests\ResetDatabaseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ImportListingsCommandTest extends KernelTestCase
{
    use ResetDatabaseTrait;

    private EntityManagerInterface $em;
    private CommandTester $command;
    private string $file;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->resetDatabase($this->em);

        $this->command = new CommandTester((new Application(self::$kernel))->find('app:import-listings'));

        $base = tempnam(sys_get_temp_dir(), 'listings_');
        unlink($base);
        $this->file = $base.'.jsonl';
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        parent::tearDown();
    }

    public function testImportsListingsAndReusesExistingHostsAndAmenities(): void
    {
        $existingHost = (new User())->setEmail('host@example.com')->setFirstName('Hélène')->setLastName('Hôte')->setPassword('hash');
        $this->em->persist($existingHost);
        $this->em->persist((new Amenity())->setName('Wi-Fi')->setSlug('wifi'));
        $this->em->flush();

        $this->writeLines(
            $this->line('Studio', 'Paris', 'host@example.com', ['Wi-Fi']),
            $this->line('Loft', 'Lyon', 'new.host@example.com', ['Wi-Fi', 'Sauna']),
            $this->line('Villa', 'Nice', 'NEW.HOST@example.com', ['Sauna']),
        );

        $exitCode = $this->command->execute(['file' => $this->file]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('3 logement(s) importé(s)', $this->command->getDisplay());
        self::assertSame(3, $this->em->getRepository(Listing::class)->count([]));

        self::assertSame(2, $this->em->getRepository(User::class)->count([]));

        self::assertSame(['sauna', 'wifi'], $this->amenitySlugs());

        $studio = $this->em->getRepository(Listing::class)->findOneBy(['title' => 'Studio']);
        self::assertSame($existingHost->getId(), $studio->getHost()->getId());
        self::assertSame('95.00', $studio->getPriceByNight());
    }

    public function testRunningTheImportTwiceCreatesNoDuplicate(): void
    {
        $this->writeLines(
            $this->line('Studio', 'Paris', 'a@example.com', ['Wi-Fi']),
            $this->line('Studio', 'Paris', 'a@example.com', ['Wi-Fi']),
        );

        $this->command->execute(['file' => $this->file]);
        self::assertSame(1, $this->em->getRepository(Listing::class)->count([]));

        $this->command->execute(['file' => $this->file]);

        self::assertSame(1, $this->em->getRepository(Listing::class)->count([]));
        self::assertStringContainsString('0 logement(s) importé(s)', $this->command->getDisplay());
    }

    public function testInvalidLinesAreReportedWithoutStoppingTheImport(): void
    {
        $this->writeLines(
            '{ pas du json',
            json_encode(['title' => 'Sans prix', 'city' => 'Paris']),
            $this->line('Valide', 'Paris', 'a@example.com', []),
            '"juste une chaîne"',
        );

        $exitCode = $this->command->execute(['file' => $this->file]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(1, $this->em->getRepository(Listing::class)->count([]));
        $display = $this->command->getDisplay();
        self::assertStringContainsString('JSON invalide', $display);
        self::assertStringContainsString('priceByNight', $display);
        self::assertStringContainsString('Un objet JSON est attendu', $display);
    }

    public function testMissingFileFails(): void
    {
        $exitCode = $this->command->execute(['file' => '/does/not/exist.jsonl']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Impossible de lire le fichier', $this->command->getDisplay());
    }

    private function line(string $title, string $city, string $hostEmail, array $amenities): string
    {
        return json_encode([
            'title' => $title,
            'description' => 'Description',
            'priceByNight' => 95,
            'city' => $city,
            'country' => 'France',
            'maxGuests' => 2,
            'host' => ['email' => $hostEmail, 'firstName' => 'Prénom', 'lastName' => 'Nom'],
            'amenities' => $amenities,
        ]);
    }

    private function writeLines(string ...$lines): void
    {
        file_put_contents($this->file, implode("\n", $lines)."\n");
    }

    private function amenitySlugs(): array
    {
        $slugs = array_map(static fn (Amenity $a) => $a->getSlug(), $this->em->getRepository(Amenity::class)->findAll());
        sort($slugs);

        return $slugs;
    }
}
