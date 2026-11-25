<?php

namespace App\Serializer;

use App\Entity\Listing;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ListingNormalizer implements NormalizerInterface
{
    public const STATS = 'listing_stats';

    public const MY_RATINGS = 'listing_my_ratings';

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $normalized = $this->normalizer->normalize($data, $format, $context);
        $id = $data->getId();

        if (\array_key_exists(self::STATS, $context)) {
            $stats = $context[self::STATS][$id] ?? null;
            $normalized['averageRating'] = $stats['averageRating'] ?? null;
            $normalized['reviewCount'] = $stats['reviewCount'] ?? 0;
        }

        if (\array_key_exists(self::MY_RATINGS, $context)) {
            $normalized['myRating'] = $context[self::MY_RATINGS][$id] ?? null;
        }

        return $normalized;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Listing
            && (\array_key_exists(self::STATS, $context) || \array_key_exists(self::MY_RATINGS, $context));
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Listing::class => false];
    }
}
