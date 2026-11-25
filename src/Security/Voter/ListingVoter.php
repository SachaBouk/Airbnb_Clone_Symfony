<?php

namespace App\Security\Voter;

use App\Entity\Listing;
use App\Entity\User;
use App\Repository\BookingRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Decides what a user may do with a listing:
 * - EDIT / DELETE: only its host;
 * - REVIEW: only a traveler (not the host) who has finished a confirmed stay there.
 *
 * @extends Voter<string, Listing>
 */
final class ListingVoter extends Voter
{
    public const EDIT = 'LISTING_EDIT';
    public const DELETE = 'LISTING_DELETE';
    public const REVIEW = 'LISTING_REVIEW';

    public function __construct(
        private readonly BookingRepository $bookingRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::EDIT, self::DELETE, self::REVIEW], true) && $subject instanceof Listing;
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

        if (!$isHost) {
            $vote?->addReason('Seul l\'hôte du logement peut le modifier ou le supprimer.');
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
