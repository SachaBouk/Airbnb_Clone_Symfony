<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function followersQuery(User $user): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->join('u.following', 'followed')
            ->andWhere('followed = :user')
            ->setParameter('user', $user)
            ->orderBy('u.id', 'ASC');
    }

    public function followingQuery(User $user): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->join('u.followers', 'follower')
            ->andWhere('follower = :user')
            ->setParameter('user', $user)
            ->orderBy('u.id', 'ASC');
    }
}
