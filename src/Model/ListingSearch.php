<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


/**
 * Query string of GET /api/listings: filters + page/limit (inherited from PaginationDTO).
 */
final readonly class ListingSearch extends PaginationDTO
{
    public function __construct(

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

        
        #[Assert\Length(max: 100)]
        public ?string $amenity = null,

        public ?\DateTimeImmutable $availableFrom = null,

        public ?\DateTimeImmutable $availableTo = null,

        int $page = 1,
        int $limit = 20,
    ) {
        parent::__construct($page, $limit);
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
