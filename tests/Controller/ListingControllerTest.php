<?php

namespace App\Tests\Controller;

use App\Entity\Amenity;
use App\Entity\Booking;
use App\Entity\Listing;
use App\Entity\Review;
use App\Entity\User;
use App\Enum\BookingStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ListingControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        // Fresh, empty test database for every test. Foreign keys are paused while
        // dropping, otherwise SQLite refuses to drop tables that still have linked rows.
        $connection = $this->em->getConnection();
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $connection->executeStatement('PRAGMA foreign_keys = OFF');
        $schemaTool->dropSchema($metadata);
        $connection->executeStatement('PRAGMA foreign_keys = ON');
        $schemaTool->createSchema($metadata);
    }

    public function testListReturnsPaginatedListingsWithoutPrivateHostData(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        $this->createListing($host, 'Paris', 2, '80.00');
        $this->createListing($host, 'Lyon', 4, '120.00');
        $this->createListing($host, 'Nice', 6, '200.00');
        $this->em->flush();

        $this->client->request('GET', '/api/listings?limit=2');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        $data = $this->json();
        self::assertSame(3, $data['total']);
        self::assertSame(2, $data['pages']);
        self::assertCount(2, $data['items']);
        self::assertSame(['id', 'firstName'], array_keys($data['items'][0]['host']));
        self::assertArrayNotHasKey('description', $data['items'][0]);
        self::assertStringNotContainsString('host@example.com', $this->client->getResponse()->getContent());
    }

    public function testListFiltersByCityGuestsAndMaxPrice(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        $this->createListing($host, 'Paris', 2, '80.00');
        $this->createListing($host, 'Paris', 4, '120.00');
        $this->createListing($host, 'Paris', 6, '300.00');
        $this->createListing($host, 'Lyon', 6, '90.00');
        $this->em->flush();

        $this->client->request('GET', '/api/listings?city=paris&guests=3&maxPrice=150');

        self::assertResponseIsSuccessful();
        $data = $this->json();
        self::assertSame(1, $data['total']);
        self::assertSame('Paris', $data['items'][0]['city']);
        self::assertSame(4, $data['items'][0]['maxGuests']);
    }

    public function testListRejectsInvalidFilters(): void
    {
        $this->client->request('GET', '/api/listings?guests=0');

        self::assertResponseStatusCodeSame(422);
        self::assertSame('guests', $this->json()['violations'][0]['propertyPath']);
    }

    public function testShowReturnsListingWithReviewsAndAverageRating(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        $traveler = $this->createUser('traveler@example.com', 'Victor');
        $listing = $this->createListing($host, 'Paris', 2, '80.00');
        $this->createReview($listing, $traveler, 5);
        $this->createReview($listing, $traveler, 4);
        $this->em->flush();

        $this->client->request('GET', '/api/listings/'.$listing->getId());

        self::assertResponseIsSuccessful();
        $data = $this->json();
        self::assertSame('Paris', $data['listing']['city']);
        self::assertArrayHasKey('description', $data['listing']);
        self::assertSame(4.5, $data['averageRating']);
        self::assertSame(2, $data['reviewCount']);
        self::assertSame(['id', 'firstName'], array_keys($data['reviews'][0]['author']));
    }

    public function testShowReturnsNullAverageWhenNoReview(): void
    {
        $listing = $this->createListing($this->createUser('host@example.com', 'Hélène'), 'Paris', 2, '80.00');
        $this->em->flush();

        $this->client->request('GET', '/api/listings/'.$listing->getId());

        self::assertResponseIsSuccessful();
        self::assertNull($this->json()['averageRating']);
        self::assertSame([], $this->json()['reviews']);
    }

    public function testShowReturns404ForUnknownListing(): void
    {
        $this->client->request('GET', '/api/listings/9999');

        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        self::assertSame(404, $this->json()['status']);
    }

    public function testListFiltersByPartialTitleAndEscapesWildcards(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        $this->createListing($host, 'Paris', 2, '80.00')->setTitle('Villa avec piscine');
        $this->createListing($host, 'Paris', 2, '80.00')->setTitle('Studio cosy');
        $this->createListing($host, 'Paris', 2, '80.00')->setTitle('Remise de 50% sur le loft');
        $this->em->flush();

        $this->client->request('GET', '/api/listings?title=VILLA');
        self::assertSame(['Villa avec piscine'], array_column($this->json()['items'], 'title'));

        $this->client->request('GET', '/api/listings?title=%25');
        self::assertSame(['Remise de 50% sur le loft'], array_column($this->json()['items'], 'title'));
    }

    public function testListFiltersByAmenityAndStillReturnsAllAmenitiesOfMatches(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        $wifi = $this->createAmenity('wifi', 'Wi-Fi');
        $pool = $this->createAmenity('pool', 'Piscine');
        $withPool = $this->createListing($host, 'Nice', 4, '150.00')->addAmenity($wifi)->addAmenity($pool);
        $this->createListing($host, 'Lyon', 4, '90.00')->addAmenity($wifi);
        $this->em->flush();
        // Like a real request: start from an empty identity map, so amenities are loaded (and sorted) from the DB.
        $this->em->clear();

        $this->client->request('GET', '/api/listings?amenity=pool');

        $data = $this->json();
        self::assertSame(1, $data['total']);
        self::assertSame($withPool->getId(), $data['items'][0]['id']);
        self::assertSame(['pool', 'wifi'], array_column($data['items'][0]['amenities'], 'slug'));
    }

    public function testPaginationCountsListingsNotJoinedAmenityRows(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        $amenities = [$this->createAmenity('wifi', 'Wi-Fi'), $this->createAmenity('pool', 'Piscine'), $this->createAmenity('tv', 'TV')];
        for ($i = 0; $i < 5; ++$i) {
            $this->createListing($host, 'Paris', 2, '80.00')->replaceAmenities($amenities);
        }
        $this->em->flush();

        $this->client->request('GET', '/api/listings?limit=2&page=3');

        $data = $this->json();
        self::assertSame(5, $data['total']);
        self::assertSame(3, $data['pages']);
        self::assertCount(1, $data['items']);
        self::assertCount(3, $data['items'][0]['amenities']);
    }

    public function testListFiltersByAvailability(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        $traveler = $this->createUser('traveler@example.com', 'Victor');
        $booked = $this->createListing($host, 'Paris', 2, '80.00');
        $cancelledOnly = $this->createListing($host, 'Lyon', 2, '80.00');
        $free = $this->createListing($host, 'Nice', 2, '80.00');
        $this->createBooking($booked, $traveler, '2027-07-10', '2027-07-15', BookingStatus::Confirmed);
        $this->createBooking($cancelledOnly, $traveler, '2027-07-10', '2027-07-15', BookingStatus::Cancelled);
        // Ends the day the search starts: no overlap.
        $this->createBooking($free, $traveler, '2027-07-05', '2027-07-12', BookingStatus::Confirmed);
        $this->em->flush();

        $this->client->request('GET', '/api/listings?availableFrom=2027-07-12&availableTo=2027-07-20');

        self::assertResponseIsSuccessful();
        $ids = array_column($this->json()['items'], 'id');
        self::assertEqualsCanonicalizing([$cancelledOnly->getId(), $free->getId()], $ids);
    }

    public function testListRejectsIncompleteOrInvertedDateRange(): void
    {
        $this->client->request('GET', '/api/listings?availableFrom=2027-07-12');
        self::assertResponseStatusCodeSame(422);
        self::assertSame('availableTo', $this->json()['violations'][0]['propertyPath']);

        $this->client->request('GET', '/api/listings?availableFrom=2027-07-12&availableTo=2027-07-10');
        self::assertResponseStatusCodeSame(422);
        self::assertSame('availableTo', $this->json()['violations'][0]['propertyPath']);
    }

    public function testCreateListing(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        $wifi = $this->createAmenity('wifi', 'Wi-Fi');
        $this->em->flush();

        $this->postJson('POST', '/api/listings', $this->validPayload($host, [$wifi->getId()]));

        self::assertResponseStatusCodeSame(201);
        $data = $this->json();
        self::assertResponseHeaderSame('Location', '/api/listings/'.$data['id']);
        self::assertSame('Cabane au bord du lac', $data['title']);
        self::assertSame('95.00', $data['priceByNight']);
        self::assertSame($host->getId(), $data['host']['id']);
        self::assertSame(['wifi'], array_column($data['amenities'], 'slug'));

        $this->client->request('GET', '/api/listings/'.$data['id']);
        self::assertResponseIsSuccessful();
    }

    public function testCreateRejectsInvalidPayload(): void
    {
        $this->postJson('POST', '/api/listings', ['title' => '', 'priceByNight' => -5]);

        self::assertResponseStatusCodeSame(422);
        $paths = array_column($this->json()['violations'], 'propertyPath');
        foreach (['title', 'description', 'priceByNight', 'city', 'country', 'maxGuests', 'hostId'] as $field) {
            self::assertContains($field, $paths);
        }
    }

    public function testCreateRejectsUnknownHostAndAmenity(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        $this->em->flush();
        $payload = ['hostId' => 9999, 'amenityIds' => [9999]] + $this->validPayload($host);

        $this->postJson('POST', '/api/listings', $payload);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['hostId', 'amenityIds'], array_column($this->json()['violations'], 'propertyPath'));
        self::assertSame(0, $this->em->getRepository(Listing::class)->count([]));
    }

    public function testUpdateReplacesFieldsAndAmenities(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        $newHost = $this->createUser('other@example.com', 'Omar');
        $wifi = $this->createAmenity('wifi', 'Wi-Fi');
        $pool = $this->createAmenity('pool', 'Piscine');
        $tv = $this->createAmenity('tv', 'TV');
        $listing = $this->createListing($host, 'Paris', 2, '80.00')->replaceAmenities([$wifi, $pool]);
        $this->em->flush();

        $payload = ['title' => 'Nouveau titre', 'priceByNight' => 110.5] + $this->validPayload($newHost, [$pool->getId(), $tv->getId()]);
        $this->postJson('PUT', '/api/listings/'.$listing->getId(), $payload);

        self::assertResponseIsSuccessful();
        $data = $this->json();
        self::assertSame('Nouveau titre', $data['title']);
        self::assertSame('110.50', $data['priceByNight']);
        self::assertSame($newHost->getId(), $data['host']['id']);
        // A JSON list, not an object, even though "wifi" was removed from the collection.
        self::assertSame(['pool', 'tv'], array_column($data['amenities'], 'slug'));
        self::assertTrue(array_is_list($data['amenities']));
    }

    public function testUpdateUnknownListingReturns404(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        $this->em->flush();

        $this->postJson('PUT', '/api/listings/9999', $this->validPayload($host));

        self::assertResponseStatusCodeSame(404);
    }

    public function testDeleteListingAlsoRemovesItsAmenityLinks(): void
    {
        $listing = $this->createListing($this->createUser('host@example.com', 'Hélène'), 'Paris', 2, '80.00')
            ->addAmenity($this->createAmenity('wifi', 'Wi-Fi'));
        $this->em->flush();
        $id = $listing->getId();

        $this->client->request('DELETE', '/api/listings/'.$id);

        self::assertResponseStatusCodeSame(204);
        $this->client->request('GET', '/api/listings/'.$id);
        self::assertResponseStatusCodeSame(404);
        $links = $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM listing_amenity WHERE listing_id = ?', [$id]);
        self::assertSame(0, (int) $links);
    }

    public function testDeleteListingWithBookingsReturns409(): void
    {
        $listing = $this->createListing($this->createUser('host@example.com', 'Hélène'), 'Paris', 2, '80.00');
        $this->createBooking($listing, $this->createUser('traveler@example.com', 'Victor'), '2027-07-10', '2027-07-15', BookingStatus::Confirmed);
        $this->em->flush();

        $this->client->request('DELETE', '/api/listings/'.$listing->getId());

        self::assertResponseStatusCodeSame(409);
        $this->client->request('GET', '/api/listings/'.$listing->getId());
        self::assertResponseIsSuccessful();
    }

    public function testAmenitiesEndpointListsAmenitiesByName(): void
    {
        $this->createAmenity('wifi', 'Wi-Fi');
        $this->createAmenity('pool', 'Piscine');
        $this->em->flush();

        $this->client->request('GET', '/api/amenities');

        self::assertResponseIsSuccessful();
        self::assertSame(['Piscine', 'Wi-Fi'], array_column($this->json(), 'name'));
        self::assertSame(['id', 'name', 'slug'], array_keys($this->json()[0]));
    }

    /**
     * @param list<int> $amenityIds
     */
    private function validPayload(User $host, array $amenityIds = []): array
    {
        return [
            'title' => 'Cabane au bord du lac',
            'description' => 'Calme absolu.',
            'priceByNight' => 95,
            'city' => 'Annecy',
            'country' => 'France',
            'maxGuests' => 3,
            'hostId' => $host->getId(),
            'amenityIds' => $amenityIds,
        ];
    }

    private function postJson(string $method, string $uri, array $payload): void
    {
        $this->client->request($method, $uri, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, \JSON_THROW_ON_ERROR));
    }

    private function createAmenity(string $slug, string $name): Amenity
    {
        $amenity = (new Amenity())->setSlug($slug)->setName($name);
        $this->em->persist($amenity);

        return $amenity;
    }

    private function createBooking(Listing $listing, User $traveler, string $from, string $to, BookingStatus $status): void
    {
        $booking = (new Booking())
            ->setArrivalDate(new \DateTimeImmutable($from))
            ->setDepartureDate(new \DateTimeImmutable($to))
            ->setGuests(1)
            ->setTotalPrice('100.00')
            ->setStatus($status)
            ->setTraveler($traveler)
            ->setListing($listing);
        $this->em->persist($booking);
    }

    private function json(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }

    private function createUser(string $email, string $firstName): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName('Test')
            ->setPassword('not-a-real-hash');
        $this->em->persist($user);

        return $user;
    }

    private function createListing(User $host, string $city, int $maxGuests, string $price): Listing
    {
        $listing = (new Listing())
            ->setTitle('Logement à '.$city)
            ->setDescription('Une description.')
            ->setPriceByNight($price)
            ->setCity($city)
            ->setCountry('France')
            ->setMaxGuests($maxGuests)
            ->setHost($host);
        $this->em->persist($listing);

        return $listing;
    }

    private function createReview(Listing $listing, User $author, int $rating): void
    {
        $review = (new Review())
            ->setRating($rating)
            ->setComment('Très bien.')
            ->setAuthor($author)
            ->setListing($listing);
        $this->em->persist($review);
    }
}
