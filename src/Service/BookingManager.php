<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Listing;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Model\BookingInput;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Books, confirms and cancels stays. Who may do it is decided by
 * ListingVoter::BOOK (create) and BookingVoter (confirm / cancel).
 *
 * Lifecycle: pending → confirmed (by the host) ; pending or confirmed → cancelled (traveler or host, before arrival).
 */
final readonly class BookingManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private BookingRepository $bookingRepository,
        private ValidatorInterface $validator,
        private LockFactory $lockFactory,
    ) {
    }

    /**
     * @throws UnprocessableEntityHttpException when the booking is invalid (e.g. too many guests)
     * @throws ConflictHttpException            when the dates are already taken
     */
    public function create(Listing $listing, User $traveler, BookingInput $input): Booking
    {
        $booking = (new Booking())
            ->setListing($listing)
            ->setTraveler($traveler)
            ->setArrivalDate($input->arrivalDate)
            ->setDepartureDate($input->departureDate)
            ->setGuests($input->guests);
        // The price is always computed here, never sent by the client.
        $booking->setTotalPrice(number_format($booking->getNights() * (float) $listing->getPriceByNight(), 2, '.', ''));

        $violations = $this->validator->validate($booking);
        if (\count($violations) > 0) {
            throw new UnprocessableEntityHttpException(previous: new ValidationFailedException($booking, $violations));
        }

        // Without the lock, two simultaneous requests could both see the dates as free and both book them.
        $lock = $this->lockFactory->createLock('listing-booking-'.$listing->getId());
        $lock->acquire(true);
        try {
            if ($this->bookingRepository->hasOverlappingBooking($listing, $input->arrivalDate, $input->departureDate)) {
                throw new ConflictHttpException('Ces dates ne sont plus disponibles pour ce logement.');
            }

            $this->em->persist($booking);
            $this->em->flush();
        } finally {
            $lock->release();
        }

        return $booking;
    }

    /**
     * @throws ConflictHttpException when the booking is not pending
     */
    public function confirm(Booking $booking): Booking
    {
        if (BookingStatus::Pending !== $booking->getStatus()) {
            throw new ConflictHttpException('Seule une réservation en attente peut être confirmée.');
        }

        $booking->setStatus(BookingStatus::Confirmed);
        $this->em->flush();

        return $booking;
    }

    /**
     * @throws ConflictHttpException when the booking is already cancelled or the stay has begun
     */
    public function cancel(Booking $booking): Booking
    {
        if (BookingStatus::Cancelled === $booking->getStatus()) {
            throw new ConflictHttpException('Cette réservation est déjà annulée.');
        }
        if ($booking->getArrivalDate() <= new \DateTimeImmutable('today')) {
            throw new ConflictHttpException('Un séjour commencé ou terminé ne peut plus être annulé.');
        }

        $booking->setStatus(BookingStatus::Cancelled);
        $this->em->flush();

        return $booking;
    }
}
