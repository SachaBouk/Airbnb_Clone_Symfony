<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * One line (one JSON object) of the file imported by app:import-listings.
 */
final readonly class ListingImportRow
{
    /**
     * @param list<string> $amenities amenity names, e.g. ["Wi-Fi", "Piscine"]
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public ?string $title = null,

        #[Assert\NotBlank]
        #[Assert\Length(max: 5000)]
        public ?string $description = null,

        #[Assert\NotNull]
        #[Assert\Positive]
        #[Assert\LessThan(100000)]
        public ?float $priceByNight = null,

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public ?string $city = null,

        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public ?string $country = null,

        #[Assert\NotNull]
        #[Assert\Range(min: 1, max: 50)]
        public ?int $maxGuests = null,

        #[Assert\NotNull]
        #[Assert\Valid]
        public ?ListingImportHost $host = null,

        #[Assert\All([new Assert\NotBlank(), new Assert\Length(max: 100)])]
        public array $amenities = [],
    ) {
    }
}
