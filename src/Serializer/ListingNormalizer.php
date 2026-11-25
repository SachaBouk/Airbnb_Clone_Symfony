<?php

namespace App\Serializer;

use App\Entity\Listing;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Custom normalizer: adds review data to a Listing, on top of its regular fields.
 *
 * The values are computed beforehand for the whole page (one SQL query each, see
 * ListingController::list()) and passed through the serialization context:
 * - STATS      → adds "averageRating" (null if no review) and "reviewCount";
 * - MY_RATINGS → adds "myRating": the logged-in user's rating, null if he did not rate it.
 *
 * Without these context keys, Listing is serialized as usual (this normalizer is skipped).
 */
final class ListingNormalizer implements NormalizerInterface
{
    /** @var string context key: array<int, array{averageRating: float, reviewCount: int}> indexed by listing id */
    public const STATS = 'listing_stats';

    /** @var string context key: array<int, int> rating indexed by listing id */
    public const MY_RATINGS = 'listing_my_ratings';

    public function __construct(
        // The built-in normalizer that produces the regular fields (groups, nested host, amenities...).
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    /**
     * @param Listing $data
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        /** @var array<string, mixed> $normalized */
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
        // false = not cacheable: support depends on the context, not only on the class.
        return [Listing::class => false];
    }
}
