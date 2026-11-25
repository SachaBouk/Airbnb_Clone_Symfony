<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

readonly class PaginationDTO
{
    public function __construct(
        #[Assert\Positive]
        public int $page = 1,

        #[Assert\Range(min: 1, max: 50)]
        public int $limit = 20,
    ) {
    }
}
