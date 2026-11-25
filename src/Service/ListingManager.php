<?php

namespace App\Service;

use App\Entity\Listing;
use App\Entity\User;
use App\Model\ListingInput;
use App\Repository\AmenityRepository;
use App\Repository\BookingRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Creates, updates and deletes listings from validated API input.
 */
final readonly class ListingManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private AmenityRepository $amenityRepository,
        private BookingRepository $bookingRepository,
        private ReviewRepository $reviewRepository,
    ) {
    }

    public function create(ListingInput $input, User $host): Listing
    {
        $listing = (new Listing())->setHost($host);
        $this->apply($listing, $input);

        $this->em->persist($listing);
        $this->em->flush();

        return $listing;
    }

    public function update(Listing $listing, ListingInput $input): Listing
    {
        $this->apply($listing, $input);
        $this->em->flush();

        return $listing;
    }

    /**
     * @throws ConflictHttpException when bookings or reviews still reference the listing
     */
    public function delete(Listing $listing): void
    {
        $hasHistory = $this->bookingRepository->count(['listing' => $listing]) > 0
            || $this->reviewRepository->count(['listing' => $listing]) > 0;

        if ($hasHistory) {
            throw new ConflictHttpException('Ce logement a des réservations ou des avis : il ne peut pas être supprimé.');
        }

        $this->em->remove($listing);
        $this->em->flush();
    }

    /**
     * The host is never taken from the input: it is set once, at creation, from the logged-in user.
     *
     * @throws UnprocessableEntityHttpException when an amenityId matches nothing
     */
    private function apply(Listing $listing, ListingInput $input): void
    {
        $violations = new ConstraintViolationList();

        $amenities = [] === $input->amenityIds ? [] : $this->amenityRepository->findBy(['id' => $input->amenityIds]);
        $foundIds = array_map(static fn ($amenity) => $amenity->getId(), $amenities);
        foreach (array_diff($input->amenityIds, $foundIds) as $missingId) {
            $violations->add($this->violation($input, 'amenityIds', 'Aucun équipement avec l\'id {{ value }}.', $missingId));
        }

        if (\count($violations) > 0) {
            // Same 422 "violations" format as #[MapRequestPayload] validation errors.
            throw new UnprocessableEntityHttpException(previous: new ValidationFailedException($input, $violations));
        }

        $listing
            ->setTitle($input->title)
            ->setDescription($input->description)
            ->setPriceByNight(number_format($input->priceByNight, 2, '.', ''))
            ->setCity($input->city)
            ->setCountry($input->country)
            ->setMaxGuests($input->maxGuests)
            ->replaceAmenities($amenities);
    }

    private function violation(ListingInput $input, string $path, string $template, int $value): ConstraintViolation
    {
        $parameters = ['{{ value }}' => (string) $value];

        return new ConstraintViolation(strtr($template, $parameters), $template, $parameters, $input, $path, $value);
    }
}
