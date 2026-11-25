<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ReviewInput
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Range(min: 1, max: 5)]
        public ?int $rating = null,

        #[Assert\NotBlank]
        #[Assert\Length(max: 2000)]
        public ?string $comment = null,
    ) {
    }
}
