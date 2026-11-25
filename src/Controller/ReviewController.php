<?php

namespace App\Controller;

use App\Entity\Listing;
use App\Entity\Review;
use App\Entity\User;
use App\Model\PaginationDTO;
use App\Model\Paginator;
use App\Model\ReviewInput;
use App\Repository\ReviewRepository;
use App\Security\Voter\ListingVoter;
use App\Security\Voter\ReviewVoter;
use App\Service\ReviewManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'app_reviews_', format: 'json')]
final class ReviewController extends AbstractController
{
    /** A review with its dates, its author (first name) and the listing it is about (id, title). */
    private const GROUPS = ['review:read', 'review:feed', 'user:public', 'all:read'];

    /**
     * Feed of the latest reviews, all listings combined.
     */
    #[Route('/reviews', name: 'feed', methods: ['GET'])]
    public function feed(
        ReviewRepository $reviewRepository,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        PaginationDTO $pagination = new PaginationDTO(),
    ): JsonResponse {
        $page = (new Paginator())->paginate($reviewRepository->feedQuery(), $pagination);

        return $this->json($page, context: ['groups' => ['paginate', ...self::GROUPS]]);
    }

    /**
     * Personal feed: latest reviews written by the users the logged-in user follows.
     */
    #[Route('/feed', name: 'following_feed', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function followingFeed(
        ReviewRepository $reviewRepository,
        #[CurrentUser] User $user,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        PaginationDTO $pagination = new PaginationDTO(),
    ): JsonResponse {
        $page = (new Paginator())->paginate($reviewRepository->followingFeedQuery($user), $pagination);

        return $this->json($page, context: ['groups' => ['paginate', ...self::GROUPS]]);
    }

    /**
     * The logged-in traveler rates listing {id} (after a finished, confirmed stay).
     */
    #[Route('/listings/{id}/reviews', name: 'create', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(ListingVoter::REVIEW, subject: 'listing')]
    public function create(
        Listing $listing,
        #[MapRequestPayload] ReviewInput $input,
        #[CurrentUser] User $user,
        ReviewManager $reviewManager,
    ): JsonResponse {
        $review = $reviewManager->create($listing, $user, $input);

        return $this->json($review, Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('app_listings_show', ['id' => $listing->getId()]),
        ], ['groups' => self::GROUPS]);
    }

    #[Route('/reviews/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted(ReviewVoter::EDIT, subject: 'review')]
    public function update(Review $review, #[MapRequestPayload] ReviewInput $input, ReviewManager $reviewManager): JsonResponse
    {
        return $this->json($reviewManager->update($review, $input), context: ['groups' => self::GROUPS]);
    }

    #[Route('/reviews/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted(ReviewVoter::DELETE, subject: 'review')]
    public function delete(Review $review, ReviewManager $reviewManager): Response
    {
        $reviewManager->delete($review);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
