<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


/**
 * Query string of GET /api/listings: filters + page/limit (inherited from PaginationDTO).
 */
final readonly class ListingSearch extends PaginationDTO
{
    public const SORT_RATING = 'rating';
    public const SORT_PRICE_ASC = 'price_asc';
    public const SORT_PRICE_DESC = 'price_desc';
    public const SORTS = [self::SORT_RATING, self::SORT_PRICE_ASC, self::SORT_PRICE_DESC];

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

        /** null = by id; "rating" = best average first; "price_asc" / "price_desc" = by price per night. */
        #[Assert\Choice(choices: self::SORTS)]
        public ?string $sort = null,

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
