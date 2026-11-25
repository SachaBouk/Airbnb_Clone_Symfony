<?php

namespace App\Security\Voter;

use App\Entity\Photo;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Only the host of the listing may delete a photo or make it the cover.
 *
 * @extends Voter<string, Photo>
 */
final class PhotoVoter extends Voter
{
    public const EDIT = 'PHOTO_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::EDIT === $attribute && $subject instanceof Photo;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        $isHost = $user instanceof User && $subject->getListing()?->getHost()?->getId() === $user->getId();

        if (!$isHost) {
            $vote?->addReason('Seul l\'hôte du logement peut gérer ses photos.');
        }

        return $isHost;
    }
}
