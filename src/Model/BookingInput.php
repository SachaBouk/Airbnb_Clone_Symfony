<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * JSON body of POST /api/listings/{id}/bookings. Dates are "YYYY-MM-DD".
 */
final readonly class BookingInput
{
    public const MAX_NIGHTS = 90;

    public function __construct(
        #[Assert\NotNull]
        #[Assert\GreaterThanOrEqual('today', message: 'La date d\'arrivée ne peut pas être dans le passé.')]
        public ?\DateTimeImmutable $arrivalDate = null,

        #[Assert\NotNull]
        #[Assert\GreaterThan(propertyPath: 'arrivalDate', message: 'La date de départ doit être après la date d\'arrivée.')]
        public ?\DateTimeImmutable $departureDate = null,

        #[Assert\NotNull]
        #[Assert\Positive]
        public ?int $guests = null,
    ) {
    }

    #[Assert\Callback]
    public function validateLength(ExecutionContextInterface $context): void
    {
        if (null === $this->arrivalDate || null === $this->departureDate) {
            return;
        }

        if ($this->arrivalDate->diff($this->departureDate)->days > self::MAX_NIGHTS) {
            $context->buildViolation('Un séjour ne peut pas dépasser {{ max }} nuits.')
                ->setParameter('{{ max }}', (string) self::MAX_NIGHTS)
                ->atPath('departureDate')
                ->addViolation();
        }
    }
}
