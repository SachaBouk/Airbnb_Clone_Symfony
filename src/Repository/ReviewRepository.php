<?php

namespace App\Repository;

use App\Entity\Listing;
use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @return list<Review> newest first, with their author already loaded
     */
    public function findForListing(Listing $listing): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('a')
            ->join('r.author', 'a')
            ->andWhere('r.listing = :listing')
            ->setParameter('listing', $listing)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return float|null rounded to one decimal, null when the listing has no review
     */
    public function averageRatingFor(Listing $listing): ?float
    {
        $average = $this->createQueryBuilder('r')
            ->select('AVG(r.rating)')
            ->andWhere('r.listing = :listing')
            ->setParameter('listing', $listing)
            ->getQuery()
            ->getSingleScalarResult();

        return null === $average ? null : round((float) $average, 1);
    }
}
