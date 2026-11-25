<?php

namespace App\DataFixtures;

use Faker\Generator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The sample photos of data/photos/, sorted by kind of place:
 * one folder per category (villa, chalet, house, apartment, loft) for covers, plus interior/ for the gallery.
 */
final class PhotoLibrary
{
    /** Listing type (see ListingTexts) => folder of its cover photos. */
    private const CATEGORY_OF_TYPE = [
        'Appartement lumineux' => 'apartment',
        'Studio cosy' => 'apartment',
        'Duplex spacieux' => 'apartment',
        'Maison de charme' => 'house',
        'Petite maison de ville' => 'house',
        'Loft moderne' => 'loft',
        'Villa avec piscine' => 'villa',
        'Chalet chaleureux' => 'chalet',
    ];

    private const INTERIOR_FOLDER = 'interior';

    public function __construct(
        #[Autowire('%kernel.project_dir%/data/photos')]
        private readonly string $directory,
    ) {
    }

    /**
     * Paths of the photos of one listing: a cover matching its type, then $interiorCount different interiors.
     *
     * @return list<string>
     */
    public function photosFor(Generator $faker, string $listingType, int $interiorCount): array
    {
        $covers = $this->files(self::CATEGORY_OF_TYPE[$listingType] ?? 'house');
        $interiors = $this->files(self::INTERIOR_FOLDER);

        return [$faker->randomElement($covers), ...$faker->randomElements($interiors, min($interiorCount, \count($interiors)))];
    }

    /**
     * @return list<string>
     */
    private function files(string $folder): array
    {
        $files = glob($this->directory.'/'.$folder.'/*.jpg') ?: [];
        if ([] === $files) {
            throw new \RuntimeException(\sprintf('No photo found in "%s/%s".', $this->directory, $folder));
        }

        return $files;
    }
}
