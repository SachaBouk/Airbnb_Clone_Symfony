<?php

namespace App\Tests\Controller;

use App\Entity\Booking;
use App\Entity\Listing;
use App\Entity\Review;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Tests\ResetDatabaseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReviewControllerTest extends WebTestCase
{
    use ResetDatabaseTrait;

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private User $host;
    private User $traveler;
    private Listing $listing;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->resetDatabase($this->em);

        $this->host = $this->createUser('host@example.com', 'Hélène');
        $this->traveler = $this->createUser('traveler@example.com', 'Victor');
        $this->listing = $this->createListing($this->host, 'Villa à Nice');
        $this->em->flush();
    }

    public function testTravelerWhoStayedCanReview(): void
    {
        $this->createBooking($this->traveler, '-10 days', '-5 days', BookingStatus::Confirmed);

        $this->send('POST', $this->reviewsUri(), ['rating' => 4, 'comment' => 'Très bon séjour'], as: $this->traveler);

        self::assertResponseStatusCodeSame(201);
        $data = $this->json();
        self::assertSame(4, $data['rating']);
        self::assertSame('Très bon séjour', $data['comment']);
        self::assertSame(['id' => $this->traveler->getId(), 'firstName' => 'Victor'], $data['author']);
        self::assertSame(['id' => $this->listing->getId(), 'title' => 'Villa à Nice'], $data['listing']);
        self::assertNotEmpty($data['createdAt']);
    }

    public function testCannotReviewWithoutAStay(): void
    {
        $this->send('POST', $this->reviewsUri(), ['rating' => 5, 'comment' => 'Jamais venu'], as: $this->traveler);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(0, $this->countReviews());
    }

    public function testCannotReviewAFutureStay(): void
    {
        $this->createBooking($this->traveler, '+5 days', '+10 days', BookingStatus::Confirmed);

        $this->send('POST', $this->reviewsUri(), ['rating' => 5, 'comment' => 'Pas encore parti'], as: $this->traveler);

        self::assertResponseStatusCodeSame(403);
    }

    public function testCannotReviewACancelledStay(): void
    {
        $this->createBooking($this->traveler, '-10 days', '-5 days', BookingStatus::Cancelled);

        $this->send('POST', $this->reviewsUri(), ['rating' => 1, 'comment' => 'Annulé'], as: $this->traveler);

        self::assertResponseStatusCodeSame(403);
    }

    public function testHostCannotReviewHisOwnListing(): void
    {
        $this->send('POST', $this->reviewsUri(), ['rating' => 5, 'comment' => 'Mon logement est parfait'], as: $this->host);

        self::assertResponseStatusCodeSame(403);
    }

    public function testCannotReviewTwiceTheSameListing(): void
    {
        $this->createBooking($this->traveler, '-10 days', '-5 days', BookingStatus::Confirmed);
        $this->send('POST', $this->reviewsUri(), ['rating' => 4, 'comment' => 'Bien'], as: $this->traveler);

        $this->send('POST', $this->reviewsUri(), ['rating' => 5, 'comment' => 'Encore'], as: $this->traveler);

        self::assertResponseStatusCodeSame(409);
        self::assertSame(1, $this->countReviews());
    }

    public function testReviewRequiresAToken(): void
    {
        $this->send('POST', $this->reviewsUri(), ['rating' => 4, 'comment' => 'Bien']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testRatingMustBeBetween1And5(): void
    {
        $this->createBooking($this->traveler, '-10 days', '-5 days', BookingStatus::Confirmed);

        $this->send('POST', $this->reviewsUri(), ['rating' => 6, 'comment' => ''], as: $this->traveler);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['rating', 'comment'], array_column($this->json()['violations'], 'propertyPath'));
    }

    public function testAuthorCanUpdateHisReview(): void
    {
        $review = $this->createReview($this->traveler, 4);

        $this->send('PUT', '/api/reviews/'.$review->getId(), ['rating' => 2, 'comment' => 'Finalement bof'], as: $this->traveler);

        self::assertResponseIsSuccessful();
        self::assertSame(2, $this->json()['rating']);
        self::assertSame('Finalement bof', $this->json()['comment']);
    }

    public function testOtherUserCannotUpdateOrDeleteAReview(): void
    {
        $review = $this->createReview($this->traveler, 4);

        $this->send('PUT', '/api/reviews/'.$review->getId(), ['rating' => 1, 'comment' => 'Piraté'], as: $this->host);
        self::assertResponseStatusCodeSame(403);

        $this->send('DELETE', '/api/reviews/'.$review->getId(), as: $this->host);
        self::assertResponseStatusCodeSame(403);
        self::assertSame(1, $this->countReviews());
    }

    public function testAuthorCanDeleteHisReview(): void
    {
        $review = $this->createReview($this->traveler, 4);

        $this->send('DELETE', '/api/reviews/'.$review->getId(), as: $this->traveler);

        self::assertResponseStatusCodeSame(204);
        self::assertSame(0, $this->countReviews());
    }

    public function testFeedListsLatestReviewsFirstAndIsPaginated(): void
    {
        $other = $this->createUser('other@example.com', 'Omar');
        $third = $this->createUser('third@example.com', 'Tina');
        $old = $this->createReview($this->traveler, 3, '-3 days');
        $newest = $this->createReview($other, 5, '-1 hour');
        $middle = $this->createReview($third, 4, '-1 day');

        $this->send('GET', '/api/reviews?limit=2');

        self::assertResponseIsSuccessful();
        $data = $this->json();
        self::assertSame(3, $data['total']);
        self::assertSame(2, $data['lastPage']);
        self::assertSame([$newest->getId(), $middle->getId()], array_column($data['items'], 'id'));
        self::assertSame(['id' => $this->listing->getId(), 'title' => 'Villa à Nice'], $data['items'][0]['listing']);
        self::assertSame(['id', 'firstName'], array_keys($data['items'][0]['author']));

        $this->send('GET', '/api/reviews?limit=2&page=2');
        self::assertSame([$old->getId()], array_column($this->json()['items'], 'id'));
    }

    private function reviewsUri(): string
    {
        return '/api/listings/'.$this->listing->getId().'/reviews';
    }

    private function send(string $method, string $uri, ?array $payload = null, ?User $as = null): void
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if (null !== $as) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.self::getContainer()->get(JWTTokenManagerInterface::class)->create($as);
        }

        $this->client->request($method, $uri, server: $server, content: null === $payload ? null : json_encode($payload));
    }

    private function json(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }

    private function countReviews(): int
    {
        return (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM review');
    }

    private function createUser(string $email, string $firstName): User
    {
        $user = (new User())->setEmail($email)->setFirstName($firstName)->setLastName('Test')->setPassword('not-a-real-hash');
        $this->em->persist($user);

        return $user;
    }

    private function createListing(User $host, string $title): Listing
    {
        $listing = (new Listing())
            ->setTitle($title)
            ->setDescription('Description')
            ->setPriceByNight('120.00')
            ->setCity('Nice')
            ->setCountry('France')
            ->setMaxGuests(4)
            ->setHost($host);
        $this->em->persist($listing);

        return $listing;
    }

    private function createBooking(User $traveler, string $arrival, string $departure, BookingStatus $status): void
    {
        $this->em->persist((new Booking())
            ->setArrivalDate(new \DateTimeImmutable($arrival.' midnight'))
            ->setDepartureDate(new \DateTimeImmutable($departure.' midnight'))
            ->setGuests(1)
            ->setTotalPrice('100.00')
            ->setStatus($status)
            ->setTraveler($traveler)
            ->setListing($this->listing));
        $this->em->flush();
    }

    private function createReview(User $author, int $rating, string $createdAt = 'now'): Review
    {
        $review = (new Review())->setRating($rating)->setComment('Commentaire')->setAuthor($author)->setListing($this->listing);
        // createdAt has no setter (it is set at creation): backdate it for the feed ordering test.
        (new \ReflectionProperty(Review::class, 'createdAt'))->setValue($review, new \DateTimeImmutable($createdAt));
        $this->em->persist($review);
        $this->em->flush();

        return $review;
    }
}
