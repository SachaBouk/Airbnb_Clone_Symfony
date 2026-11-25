<?php

namespace App\Controller;

use App\Entity\Listing;
use App\Entity\User;
use App\Model\ListingInput;
use App\Model\ListingSearch;
use App\Repository\ListingRepository;
use App\Repository\ReviewRepository;
use App\Security\Voter\ListingVoter;
use App\Serializer\ListingNormalizer;
use App\Service\ListingManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/listings', name: 'app_listings_', format: 'json')]
final class ListingController extends AbstractController
{
    private const DETAIL_GROUPS = ['listing:read', 'user:public', 'all:read'];

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        ListingRepository $listingRepository,
        ReviewRepository $reviewRepository,
        #[CurrentUser] ?User $user,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ListingSearch $search = new ListingSearch(),
    ): JsonResponse {
        $page = $listingRepository->search($search);
        $ids = array_map(static fn (Listing $listing) => $listing->getId(), $page->items);

        $context = [
            'groups' => ['paginate', 'listing:list', 'user:public'],
            ListingNormalizer::STATS => $reviewRepository->statsForListings($ids),
        ];
        if (null !== $user) {
            $context[ListingNormalizer::MY_RATINGS] = $reviewRepository->ratingsByAuthor($user, $ids);
        }

        return $this->json($page, context: $context);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Listing $listing, ReviewRepository $reviewRepository): JsonResponse
    {
        $reviews = $reviewRepository->findForListing($listing);

        return $this->json([
            'listing' => $listing,
            'averageRating' => $reviewRepository->averageRatingFor($listing),
            'reviewCount' => \count($reviews),
            'reviews' => $reviews,
        ], context: ['groups' => [...self::DETAIL_GROUPS, 'review:read']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        #[MapRequestPayload] ListingInput $input,
        #[CurrentUser] User $user,
        ListingManager $manager,
    ): JsonResponse {
        $listing = $manager->create($input, $user);

        return $this->json($listing, Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('app_listings_show', ['id' => $listing->getId()]),
        ], ['groups' => self::DETAIL_GROUPS]);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted(ListingVoter::EDIT, subject: 'listing')]
    public function update(Listing $listing, #[MapRequestPayload] ListingInput $input, ListingManager $manager): JsonResponse
    {
        return $this->json($manager->update($listing, $input), context: ['groups' => self::DETAIL_GROUPS]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted(ListingVoter::DELETE, subject: 'listing')]
    public function delete(Listing $listing, ListingManager $manager): Response
    {
        $manager->delete($listing);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
