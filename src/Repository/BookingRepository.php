<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Listing;
use App\Entity\User;
use App\Enum\BookingStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
