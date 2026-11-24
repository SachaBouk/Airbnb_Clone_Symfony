<?php

namespace App\Controller;

use App\Dto\ListingInput;
use App\Dto\ListingSearch;
use App\Entity\Listing;
use App\Repository\ListingRepository;
use App\Repository\ReviewRepository;
use App\Service\ListingManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/listings', name: 'api_listings_', format: 'json')]
final class ListingController extends AbstractController
{
    private const DETAIL_GROUPS = ['listing:read', 'user:public'];

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        ListingRepository $listingRepository,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ListingSearch $search = new ListingSearch(),
    ): JsonResponse {
        $page = $listingRepository->search($search);

        return $this->json([
            'items' => $page->getItems(),
            'page' => $search->page,
            'limit' => $search->limit,
            'total' => $page->getTotalCount(),
            'pages' => $page->getPageCount(),
        ], context: ['groups' => ['listing:list', 'user:public']]);
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
    public function create(#[MapRequestPayload] ListingInput $input, ListingManager $manager): JsonResponse
    {
        $listing = $manager->create($input);

        return $this->json($listing, Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('api_listings_show', ['id' => $listing->getId()]),
        ], ['groups' => self::DETAIL_GROUPS]);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(Listing $listing, #[MapRequestPayload] ListingInput $input, ListingManager $manager): JsonResponse
    {
        return $this->json($manager->update($listing, $input), context: ['groups' => self::DETAIL_GROUPS]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Listing $listing, ListingManager $manager): Response
    {
        $manager->delete($listing);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
