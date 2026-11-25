<?php

namespace App\Repository;

use App\Entity\Listing;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * Latest reviews first, with author and listing loaded (GET /api/reviews).
     */
    public function feedQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('r')
            ->addSelect('a', 'l')
            ->join('r.author', 'a')
            ->join('r.listing', 'l')
            ->orderBy('r.createdAt', 'DESC')
            ->addOrderBy('r.id', 'DESC');
    }

    /**
     * Average rating and review count of several listings, in a single query.
     * Listings without review are absent from the result.
     *
     * @param list<int> $listingIds
     *
     * @return array<int, array{averageRating: float, reviewCount: int}> indexed by listing id
     */
    public function statsForListings(array $listingIds): array
    {
        if ([] === $listingIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.listing) AS listingId', 'AVG(r.rating) AS average', 'COUNT(r.id) AS total')
            ->andWhere('r.listing IN (:ids)')
            ->setParameter('ids', $listingIds)
            ->groupBy('r.listing')
            ->getQuery()
            ->getArrayResult();

        $stats = [];
        foreach ($rows as $row) {
            $stats[(int) $row['listingId']] = [
                'averageRating' => round((float) $row['average'], 1),
                'reviewCount' => (int) $row['total'],
            ];
        }

        return $stats;
    }

    /**
     * The rating $author gave to each of these listings, in a single query.
     *
     * @param list<int> $listingIds
     *
     * @return array<int, int> rating indexed by listing id (unrated listings are absent)
     */
    public function ratingsByAuthor(User $author, array $listingIds): array
    {
        if ([] === $listingIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.listing) AS listingId', 'r.rating')
            ->andWhere('r.author = :author')
            ->andWhere('r.listing IN (:ids)')
            ->setParameter('author', $author)
            ->setParameter('ids', $listingIds)
            ->getQuery()
            ->getArrayResult();

        return array_column(array_map(
            static fn (array $row) => ['id' => (int) $row['listingId'], 'rating' => (int) $row['rating']],
            $rows,
        ), 'rating', 'id');
    }
}
