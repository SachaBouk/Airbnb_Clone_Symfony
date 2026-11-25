<?php

namespace App\Repository;

use App\Model\ListingSearch;
use App\Model\Paginator;
use App\Entity\Booking;
use App\Entity\Listing;
use App\Enum\BookingStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class ListingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Listing::class);
    }

    /**
     * @return Paginator<Listing>
     */
    public function search(ListingSearch $search): Paginator
    {
        $qb = $this->createQueryBuilder('l')
            ->addSelect('h', 'a')
            ->join('l.host', 'h')
            ->leftJoin('l.amenities', 'a')
            ->orderBy('l.id', 'ASC')
            ->addOrderBy('a.name', 'ASC');

        if (null !== $search->title) {
            // "!" escapes LIKE wildcards typed by the user (SQLite has no default escape character).
            $qb->andWhere("LOWER(l.title) LIKE LOWER(:title) ESCAPE '!'")
                ->setParameter('title', '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search->title).'%');
        }
        if (null !== $search->city) {
            $qb->andWhere('LOWER(l.city) = LOWER(:city)')->setParameter('city', $search->city);
        }
        if (null !== $search->country) {
            $qb->andWhere('LOWER(l.country) = LOWER(:country)')->setParameter('country', $search->country);
        }
        if (null !== $search->guests) {
            $qb->andWhere('l.maxGuests >= :guests')->setParameter('guests', $search->guests);
        }
        if (null !== $search->maxPrice) {
            $qb->andWhere('l.priceByNight <= :maxPrice')->setParameter('maxPrice', $search->maxPrice);
        }
        if (null !== $search->amenity) {

            $qb->andWhere(\sprintf(
                'EXISTS (SELECT 1 FROM %s fl JOIN fl.amenities fa WHERE fl = l AND fa.slug = :amenity)',
                Listing::class,
            ))->setParameter('amenity', $search->amenity);
        }
        if ($search->hasAvailabilityFilter()) {

            $qb->andWhere(\sprintf(
                'NOT EXISTS (SELECT 1 FROM %s b WHERE b.listing = l AND b.status IN (:blockingStatuses)
                    AND b.arrivalDate < :availableTo AND b.departureDate > :availableFrom)',
                Booking::class,
            ))
                ->setParameter('blockingStatuses', [BookingStatus::Pending->value, BookingStatus::Confirmed->value])
                ->setParameter('availableFrom', $search->availableFrom, Types::DATE_IMMUTABLE)
                ->setParameter('availableTo', $search->availableTo, Types::DATE_IMMUTABLE);
        }

        return (new Paginator())->paginate($qb, $search);
    }
}
