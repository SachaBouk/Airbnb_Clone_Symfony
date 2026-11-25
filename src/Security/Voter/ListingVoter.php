<?php

namespace App\Security\Voter;

use App\Entity\Listing;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Decides who may change a listing: only its host.
 *
 * @extends Voter<string, Listing>
 */
final class ListingVoter extends Voter
{
    public const EDIT = 'LISTING_EDIT';
    public const DELETE = 'LISTING_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::EDIT, self::DELETE], true) && $subject instanceof Listing;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $isHost = $subject->getHost()?->getId() === $user->getId();
        if (!$isHost) {
            $vote?->addReason('Seul l\'hôte du logement peut le modifier ou le supprimer.');
        }

        return $isHost;
    }
}
