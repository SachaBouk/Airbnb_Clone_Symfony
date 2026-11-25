<?php

namespace App\DataFixtures;

use Faker\Generator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class PhotoLibrary
{
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

    public function photosFor(Generator $faker, string $listingType, int $interiorCount): array
    {
        $covers = $this->files(self::CATEGORY_OF_TYPE[$listingType] ?? 'house');
        $interiors = $this->files(self::INTERIOR_FOLDER);

        return [$faker->randomElement($covers), ...$faker->randomElements($interiors, min($interiorCount, \count($interiors)))];
    }

    private function files(string $folder): array
    {
        $files = glob($this->directory.'/'.$folder.'/*.jpg') ?: [];
        if ([] === $files) {
            throw new \RuntimeException(\sprintf('No photo found in "%s/%s".', $this->directory, $folder));
        }

        return $files;
    }
}
