<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Listing;
use App\Entity\User;
use App\Model\BookingInput;
use App\Model\PaginationDTO;
use App\Model\Paginator;
use App\Repository\BookingRepository;
use App\Security\Voter\BookingVoter;
use App\Security\Voter\ListingVoter;
use App\Service\BookingManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'app_bookings_', format: 'json')]
final class BookingController extends AbstractController
{
    /** A booking with its traveler (first name) and its listing (id, title, city, country). */
    private const GROUPS = ['booking:read', 'user:public'];

    /**
     * The logged-in user books listing {id}. The booking starts "pending" until the host confirms it.
     */
    #[Route('/listings/{id}/bookings', name: 'create', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(ListingVoter::BOOK, subject: 'listing')]
    public function create(
        Listing $listing,
        #[MapRequestPayload] BookingInput $input,
        #[CurrentUser] User $user,
        BookingManager $bookingManager,
    ): JsonResponse {
        $booking = $bookingManager->create($listing, $user, $input);

        return $this->json($booking, Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('app_bookings_show', ['id' => $booking->getId()]),
        ], ['groups' => self::GROUPS]);
    }

    /**
     * The trips of the logged-in user, latest arrival first.
     */
    #[Route('/bookings', name: 'mine', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function mine(
        BookingRepository $bookingRepository,
        #[CurrentUser] User $user,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        PaginationDTO $pagination = new PaginationDTO(),
    ): JsonResponse {
        $page = (new Paginator())->paginate($bookingRepository->forTravelerQuery($user), $pagination);

        return $this->json($page, context: ['groups' => ['paginate', ...self::GROUPS]]);
    }

    /**
     * The bookings of listing {id}, for its host.
     */
    #[Route('/listings/{id}/bookings', name: 'for_listing', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(ListingVoter::VIEW_BOOKINGS, subject: 'listing')]
    public function forListing(
        Listing $listing,
        BookingRepository $bookingRepository,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        PaginationDTO $pagination = new PaginationDTO(),
    ): JsonResponse {
        $page = (new Paginator())->paginate($bookingRepository->forListingQuery($listing), $pagination);

        return $this->json($page, context: ['groups' => ['paginate', ...self::GROUPS]]);
    }

    #[Route('/bookings/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(BookingVoter::VIEW, subject: 'booking')]
    public function show(Booking $booking): JsonResponse
    {
        return $this->json($booking, context: ['groups' => self::GROUPS]);
    }

    #[Route('/bookings/{id}/confirm', name: 'confirm', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(BookingVoter::CONFIRM, subject: 'booking')]
    public function confirm(Booking $booking, BookingManager $bookingManager): JsonResponse
    {
        return $this->json($bookingManager->confirm($booking), context: ['groups' => self::GROUPS]);
    }

    #[Route('/bookings/{id}/cancel', name: 'cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(BookingVoter::CANCEL, subject: 'booking')]
    public function cancel(Booking $booking, BookingManager $bookingManager): JsonResponse
    {
        return $this->json($bookingManager->cancel($booking), context: ['groups' => self::GROUPS]);
    }
}
