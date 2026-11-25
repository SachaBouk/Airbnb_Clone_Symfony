<?php

namespace App\Tests\Entity;

use App\Entity\Listing;
use App\Entity\Review;
use App\Entity\User;
use App\Tests\ResetDatabaseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The OneToMany (User → listings, User → reviews) and the self-referencing
 * ManyToMany (followers) are loaded back correctly from the database.
 */
final class UserRelationsTest extends KernelTestCase
{
    use ResetDatabaseTrait;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->resetDatabase($this->em);
    }

    public function testUserListsTheListingsHeHostsAndTheReviewsHeWrote(): void
    {
        $host = $this->createUser('host@example.com');
        $author = $this->createUser('author@example.com');
        $listing = $this->createListing($host, 'Studio');
        $this->createListing($host, 'Villa');
        $this->createReview($listing, $author, 5);
        $this->createReview($listing, $author, 3);
        $this->em->flush();
        $this->em->clear();

        $host = $this->em->find(User::class, $host->getId());
        $author = $this->em->find(User::class, $author->getId());

        self::assertEqualsCanonicalizing(['Studio', 'Villa'], $host->getListings()->map(fn (Listing $l) => $l->getTitle())->toArray());
        self::assertCount(0, $host->getReviews());
        self::assertCount(2, $author->getReviews());
        self::assertCount(0, $author->getListings());
    }

    public function testFollowIsStoredOnBothSidesAndCannotBeDuplicated(): void
    {
        $alice = $this->createUser('alice@example.com');
        $bob = $this->createUser('bob@example.com');
        $alice->follow($bob)->follow($bob);
        $this->em->flush();
        $this->em->clear();

        $alice = $this->em->find(User::class, $alice->getId());
        $bob = $this->em->find(User::class, $bob->getId());

        self::assertTrue($alice->isFollowing($bob));
        self::assertFalse($bob->isFollowing($alice));
        self::assertCount(1, $alice->getFollowing());
        self::assertSame([$alice->getId()], $bob->getFollowers()->map(fn (User $u) => $u->getId())->getValues());
    }

    public function testDeletingAUserRemovesItsFollowLinks(): void
    {
        $alice = $this->createUser('alice@example.com');
        $bob = $this->createUser('bob@example.com');
        $alice->follow($bob);
        $bob->follow($alice);
        $this->em->flush();
        $bobId = $bob->getId();

        $this->em->remove($alice);
        $this->em->flush();
        $this->em->clear();

        self::assertCount(0, $this->em->find(User::class, $bobId)->getFollowers());
        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM user_follow'));
    }

    private function createUser(string $email): User
    {
        $user = (new User())->setEmail($email)->setFirstName('Test')->setLastName('Test')->setPassword('not-a-real-hash');
        $this->em->persist($user);

        return $user;
    }

    private function createListing(User $host, string $title): Listing
    {
        $listing = (new Listing())
            ->setTitle($title)
            ->setDescription('Description')
            ->setPriceByNight('80.00')
            ->setCity('Paris')
            ->setCountry('France')
            ->setMaxGuests(2)
            ->setHost($host);
        $this->em->persist($listing);

        return $listing;
    }

    private function createReview(Listing $listing, User $author, int $rating): void
    {
        $review = (new Review())->setRating($rating)->setComment('Commentaire')->setAuthor($author)->setListing($listing);
        $this->em->persist($review);
    }
}
