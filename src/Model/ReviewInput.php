<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * JSON body of POST /api/listings/{id}/reviews and PUT /api/reviews/{id}.
 */
final readonly class ReviewInput
{
    public function __construct(
        /** 1 to 5 stars, like Airbnb. */
        #[Assert\NotNull]
        #[Assert\Range(min: 1, max: 5)]
        public ?int $rating = null,

        #[Assert\NotBlank]
        #[Assert\Length(max: 2000)]
        public ?string $comment = null,
    ) {
    }
}
