<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ListingImportHost
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public ?string $email = null,

        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public ?string $firstName = null,

        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public ?string $lastName = null,
    ) {
    }
}
