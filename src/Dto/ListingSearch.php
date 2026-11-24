<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Query string filters of GET /api/listings.
 */
final readonly class ListingSearch
{
    public function __construct(
        /** Partial, case-insensitive match on the title. */
        #[Assert\Length(max: 255)]
        public ?string $title = null,

        #[Assert\Length(max: 255)]
        public ?string $city = null,

        #[Assert\Length(max: 100)]
        public ?string $country = null,

        #[Assert\Positive]
        public ?int $guests = null,

        #[Assert\Positive]
        public ?float $maxPrice = null,

        /** Amenity slug, e.g. "wifi". */
        #[Assert\Length(max: 100)]
        public ?string $amenity = null,

        /** With availableTo: only listings with no pending or confirmed booking overlapping these dates. */
        public ?\DateTimeImmutable $availableFrom = null,

        public ?\DateTimeImmutable $availableTo = null,

        #[Assert\Positive]
        public int $page = 1,

        #[Assert\Range(min: 1, max: 50)]
        public int $limit = 20,
    ) {
    }

    #[Assert\Callback]
    public function validateAvailability(ExecutionContextInterface $context): void
    {
        if ((null === $this->availableFrom) !== (null === $this->availableTo)) {
            $context->buildViolation('availableFrom et availableTo doivent être fournis ensemble.')
                ->atPath(null === $this->availableFrom ? 'availableFrom' : 'availableTo')
                ->addViolation();

            return;
        }

        if (null !== $this->availableFrom && $this->availableTo <= $this->availableFrom) {
            $context->buildViolation('availableTo doit être après availableFrom.')
                ->atPath('availableTo')
                ->addViolation();
        }
    }

    public function hasAvailabilityFilter(): bool
    {
        return null !== $this->availableFrom && null !== $this->availableTo;
    }
}
