<?php

namespace App\Security\Voter;

use App\Entity\Booking;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Decides what a user may do with a booking:
 * - VIEW / CANCEL: its traveler or the host of the listing;
 * - CONFIRM: only the host of the listing.
 *
 * @extends Voter<string, Booking>
 */
final class BookingVoter extends Voter
{
    public const VIEW = 'BOOKING_VIEW';
    public const CONFIRM = 'BOOKING_CONFIRM';
    public const CANCEL = 'BOOKING_CANCEL';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::CONFIRM, self::CANCEL], true) && $subject instanceof Booking;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $isHost = $subject->getListing()?->getHost()?->getId() === $user->getId();
        $isTraveler = $subject->getTraveler()?->getId() === $user->getId();

        $allowed = match ($attribute) {
            self::CONFIRM => $isHost,
            default => $isHost || $isTraveler,
        };

        if (!$allowed) {
            $vote?->addReason(self::CONFIRM === $attribute
                ? 'Seul l\'hôte du logement peut confirmer une réservation.'
                : 'Cette réservation ne vous concerne pas.');
        }

        return $allowed;
    }
}
