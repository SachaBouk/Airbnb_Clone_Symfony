<?php

namespace App\Service;

use App\Entity\Listing;
use App\Entity\Review;
use App\Entity\User;
use App\Model\ReviewInput;
use App\Repository\ReviewRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class ReviewManager
{
    private const ALREADY_REVIEWED = 'Vous avez déjà laissé un avis sur ce logement : modifiez-le plutôt.';

    public function __construct(
        private EntityManagerInterface $em,
        private ReviewRepository $reviewRepository,
    ) {
    }

    public function create(Listing $listing, User $author, ReviewInput $input): Review
    {
        if (null !== $this->reviewRepository->findOneBy(['listing' => $listing, 'author' => $author])) {
            throw new ConflictHttpException(self::ALREADY_REVIEWED);
        }

        $review = (new Review())
            ->setListing($listing)
            ->setAuthor($author)
            ->setRating($input->rating)
            ->setComment($input->comment);

        $this->em->persist($review);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new ConflictHttpException(self::ALREADY_REVIEWED, $e);
        }

        return $review;
    }

    public function update(Review $review, ReviewInput $input): Review
    {
        $review->setRating($input->rating)->setComment($input->comment);
        $this->em->flush();

        return $review;
    }

    public function delete(Review $review): void
    {
        $this->em->remove($review);
        $this->em->flush();
    }
}
