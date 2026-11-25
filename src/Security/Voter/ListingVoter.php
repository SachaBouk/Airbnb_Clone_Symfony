<?php

namespace App\Security\Voter;

use App\Entity\Listing;
use App\Entity\User;
use App\Repository\BookingRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ListingVoter extends Voter
{
    public const EDIT = 'LISTING_EDIT';
    public const DELETE = 'LISTING_DELETE';
    public const REVIEW = 'LISTING_REVIEW';
    public const BOOK = 'LISTING_BOOK';
    public const VIEW_BOOKINGS = 'LISTING_VIEW_BOOKINGS';

    private const ATTRIBUTES = [self::EDIT, self::DELETE, self::REVIEW, self::BOOK, self::VIEW_BOOKINGS];

    public function __construct(
        private readonly BookingRepository $bookingRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, self::ATTRIBUTES, true) && $subject instanceof Listing;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $isHost = $subject->getHost()?->getId() === $user->getId();

        if (self::REVIEW === $attribute) {
            return $this->canReview($subject, $user, $isHost, $vote);
        }

        if (self::BOOK === $attribute) {
            if ($isHost) {
                $vote?->addReason('Un hôte ne peut pas réserver son propre logement.');
            }

            return !$isHost;
        }

        if (!$isHost) {
            $vote?->addReason(self::VIEW_BOOKINGS === $attribute
                ? 'Seul l\'hôte du logement peut voir ses réservations.'
                : 'Seul l\'hôte du logement peut le modifier ou le supprimer.');
        }

        return $isHost;
    }

    private function canReview(Listing $listing, User $user, bool $isHost, ?Vote $vote): bool
    {
        if ($isHost) {
            $vote?->addReason('Un hôte ne peut pas noter son propre logement.');

            return false;
        }

        if (!$this->bookingRepository->hasCompletedStay($user, $listing)) {
            $vote?->addReason('Il faut avoir terminé un séjour confirmé dans ce logement pour le noter.');

            return false;
        }

        return true;
    }
}
