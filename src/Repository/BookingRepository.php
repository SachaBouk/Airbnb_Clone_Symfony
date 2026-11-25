<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Listing;
use App\Entity\User;
use App\Enum\BookingStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * True when $traveler has a confirmed booking at $listing that is already over:
     * the condition to be allowed to review it.
     */
    public function hasCompletedStay(User $traveler, Listing $listing): bool
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.traveler = :traveler')
            ->andWhere('b.listing = :listing')
            ->andWhere('b.status = :confirmed')
            ->andWhere('b.departureDate <= :today')
            ->setParameter('traveler', $traveler)
            ->setParameter('listing', $listing)
            ->setParameter('confirmed', BookingStatus::Confirmed->value)
            ->setParameter('today', new \DateTimeImmutable('today'), Types::DATE_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * True when a pending or confirmed booking of $listing overlaps [$arrival, $departure[.
     * Two stays overlap when each one starts before the other ends (checkout day = next arrival day is fine).
     */
    public function hasOverlappingBooking(Listing $listing, \DateTimeImmutable $arrival, \DateTimeImmutable $departure): bool
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.listing = :listing')
            ->andWhere('b.status IN (:blockingStatuses)')
            ->andWhere('b.arrivalDate < :departure')
            ->andWhere('b.departureDate > :arrival')
            ->setParameter('listing', $listing)
            ->setParameter('blockingStatuses', [BookingStatus::Pending->value, BookingStatus::Confirmed->value])
            ->setParameter('arrival', $arrival, Types::DATE_IMMUTABLE)
            ->setParameter('departure', $departure, Types::DATE_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * The trips of $traveler, latest arrival first (GET /api/bookings).
     */
    public function forTravelerQuery(User $traveler): QueryBuilder
    {
        return $this->createQueryBuilder('b')
            ->addSelect('l')
            ->join('b.listing', 'l')
            ->andWhere('b.traveler = :traveler')
            ->setParameter('traveler', $traveler)
            ->orderBy('b.arrivalDate', 'DESC')
            ->addOrderBy('b.id', 'DESC');
    }

    /**
     * The bookings of $listing, next arrival first (GET /api/listings/{id}/bookings, for its host).
     */
    public function forListingQuery(Listing $listing): QueryBuilder
    {
        return $this->createQueryBuilder('b')
            ->addSelect('t')
            ->join('b.traveler', 't')
            ->andWhere('b.listing = :listing')
            ->setParameter('listing', $listing)
            ->orderBy('b.arrivalDate', 'ASC')
            ->addOrderBy('b.id', 'ASC');
    }
}
