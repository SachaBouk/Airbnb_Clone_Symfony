<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final readonly class FollowManager
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function follow(User $follower, User $followed): void
    {
        if ($follower->getId() === $followed->getId()) {
            throw new UnprocessableEntityHttpException('Vous ne pouvez pas vous suivre vous-même.');
        }

        $follower->follow($followed);
        $this->em->flush();
    }

    public function unfollow(User $follower, User $followed): void
    {
        $follower->unfollow($followed);
        $this->em->flush();
    }
}
