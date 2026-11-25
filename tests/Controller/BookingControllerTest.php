<?php

namespace App\Tests\Controller;

use App\Entity\Booking;
use App\Entity\Listing;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Tests\ResetDatabaseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class BookingControllerTest extends WebTestCase
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
        $this->listing = (new Listing())
            ->setTitle('Villa à Nice')
            ->setDescription('Description')
            ->setPriceByNight('120.00')
            ->setCity('Nice')
            ->setCountry('France')
            ->setMaxGuests(4)
            ->setHost($this->host);
        $this->em->persist($this->listing);
        $this->em->flush();
    }

    public function testTravelerBooksAndTheServerComputesThePrice(): void
    {
        $this->send('POST', $this->bookingsUri(), $this->payload('+10 days', '+13 days', 2), as: $this->traveler);

        self::assertResponseStatusCodeSame(201);
        $data = $this->json();
        self::assertResponseHeaderSame('Location', '/api/bookings/'.$data['id']);
        self::assertSame('pending', $data['status']);
        self::assertSame(3, $data['nights']);
        self::assertSame('360.00', $data['totalPrice']);
        self::assertSame($this->date('+10 days'), $data['arrivalDate']);
        self::assertSame(['id' => $this->traveler->getId(), 'firstName' => 'Victor'], $data['traveler']);
        self::assertSame('Villa à Nice', $data['listing']['title']);
    }

    public function testPriceSentByTheClientIsIgnored(): void
    {
        $this->send('POST', $this->bookingsUri(), ['totalPrice' => '1.00'] + $this->payload('+10 days', '+12 days', 1), as: $this->traveler);

        self::assertResponseStatusCodeSame(201);
        self::assertSame('240.00', $this->json()['totalPrice']);
    }

    public function testOverlappingDatesAreRejected(): void
    {
        $this->createBooking($this->createUser('other@example.com', 'Omar'), '+10 days', '+15 days', BookingStatus::Confirmed);

        $this->send('POST', $this->bookingsUri(), $this->payload('+14 days', '+18 days', 1), as: $this->traveler);

        self::assertResponseStatusCodeSame(409);
    }

    public function testDatesFreedByACancellationOrTouchingAnotherStayCanBeBooked(): void
    {
        $other = $this->createUser('other@example.com', 'Omar');
        $this->createBooking($other, '+10 days', '+15 days', BookingStatus::Cancelled);
        $this->createBooking($other, '+20 days', '+25 days', BookingStatus::Pending);

        // Overlaps only the cancelled stay, and leaves the day the pending one starts.
        $this->send('POST', $this->bookingsUri(), $this->payload('+12 days', '+20 days', 1), as: $this->traveler);

        self::assertResponseStatusCodeSame(201);
    }

    public function testHostCannotBookHisOwnListing(): void
    {
        $this->send('POST', $this->bookingsUri(), $this->payload('+10 days', '+12 days', 1), as: $this->host);

        self::assertResponseStatusCodeSame(403);
    }

    public function testBookingRequiresAToken(): void
    {
        $this->send('POST', $this->bookingsUri(), $this->payload('+10 days', '+12 days', 1));

        self::assertResponseStatusCodeSame(401);
    }

    public function testInvalidDatesAreRejected(): void
    {
        $this->send('POST', $this->bookingsUri(), $this->payload('-3 days', '-5 days', 1), as: $this->traveler);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['arrivalDate', 'departureDate'], array_column($this->json()['violations'], 'propertyPath'));
    }

    public function testStayLongerThan90NightsIsRejected(): void
    {
        $this->send('POST', $this->bookingsUri(), $this->payload('+10 days', '+101 days', 1), as: $this->traveler);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('departureDate', $this->json()['violations'][0]['propertyPath']);
    }

    public function testTooManyGuestsAreRejected(): void
    {
        $this->send('POST', $this->bookingsUri(), $this->payload('+10 days', '+12 days', 5), as: $this->traveler);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('guests', $this->json()['violations'][0]['propertyPath']);
    }

    public function testTravelerListsHisTripsLatestFirst(): void
    {
        $older = $this->createBooking($this->traveler, '+5 days', '+6 days', BookingStatus::Confirmed);
        $newer = $this->createBooking($this->traveler, '+30 days', '+32 days', BookingStatus::Pending);
        $this->createBooking($this->createUser('other@example.com', 'Omar'), '+40 days', '+41 days', BookingStatus::Pending);

        $this->send('GET', '/api/bookings', as: $this->traveler);

        self::assertResponseIsSuccessful();
        $data = $this->json();
        self::assertSame(2, $data['total']);
        self::assertSame([$newer->getId(), $older->getId()], array_column($data['items'], 'id'));

        $this->send('GET', '/api/bookings');
        self::assertResponseStatusCodeSame(401);
    }

    public function testOnlyTheHostSeesTheBookingsOfHisListing(): void
    {
        $this->createBooking($this->traveler, '+5 days', '+6 days', BookingStatus::Pending);

        $this->send('GET', $this->bookingsUri(), as: $this->host);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->json()['total']);

        $this->send('GET', $this->bookingsUri(), as: $this->traveler);
        self::assertResponseStatusCodeSame(403);
    }

    public function testOnlyTheTravelerAndTheHostCanSeeABooking(): void
    {
        $booking = $this->createBooking($this->traveler, '+5 days', '+6 days', BookingStatus::Pending);

        foreach ([$this->traveler, $this->host] as $allowed) {
            $this->send('GET', '/api/bookings/'.$booking->getId(), as: $allowed);
            self::assertResponseIsSuccessful();
        }

        $this->send('GET', '/api/bookings/'.$booking->getId(), as: $this->createUser('stranger@example.com', 'Sam'));
        self::assertResponseStatusCodeSame(403);
    }

    public function testHostConfirmsAPendingBookingOnce(): void
    {
        $booking = $this->createBooking($this->traveler, '+5 days', '+6 days', BookingStatus::Pending);

        $this->send('POST', '/api/bookings/'.$booking->getId().'/confirm', as: $this->traveler);
        self::assertResponseStatusCodeSame(403);

        $this->send('POST', '/api/bookings/'.$booking->getId().'/confirm', as: $this->host);
        self::assertResponseIsSuccessful();
        self::assertSame('confirmed', $this->json()['status']);

        $this->send('POST', '/api/bookings/'.$booking->getId().'/confirm', as: $this->host);
        self::assertResponseStatusCodeSame(409);
    }

    public function testTravelerOrHostCancelsBeforeArrival(): void
    {
        $byTraveler = $this->createBooking($this->traveler, '+5 days', '+6 days', BookingStatus::Confirmed);
        $byHost = $this->createBooking($this->traveler, '+10 days', '+12 days', BookingStatus::Pending);

        $this->send('POST', '/api/bookings/'.$byTraveler->getId().'/cancel', as: $this->traveler);
        self::assertSame('cancelled', $this->json()['status']);

        $this->send('POST', '/api/bookings/'.$byHost->getId().'/cancel', as: $this->host);
        self::assertSame('cancelled', $this->json()['status']);

        $this->send('POST', '/api/bookings/'.$byHost->getId().'/cancel', as: $this->host);
        self::assertResponseStatusCodeSame(409);
    }

    public function testStartedStayCannotBeCancelled(): void
    {
        $started = $this->createBooking($this->traveler, 'today', '+2 days', BookingStatus::Confirmed);

        $this->send('POST', '/api/bookings/'.$started->getId().'/cancel', as: $this->traveler);

        self::assertResponseStatusCodeSame(409);
    }

    private function bookingsUri(): string
    {
        return '/api/listings/'.$this->listing->getId().'/bookings';
    }

    private function payload(string $arrival, string $departure, int $guests): array
    {
        return ['arrivalDate' => $this->date($arrival), 'departureDate' => $this->date($departure), 'guests' => $guests];
    }

    private function date(string $relative): string
    {
        return (new \DateTimeImmutable($relative))->format('Y-m-d');
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

    private function createUser(string $email, string $firstName): User
    {
        $user = (new User())->setEmail($email)->setFirstName($firstName)->setLastName('Test')->setPassword('not-a-real-hash');
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function createBooking(User $traveler, string $arrival, string $departure, BookingStatus $status): Booking
    {
        $booking = (new Booking())
            ->setArrivalDate(new \DateTimeImmutable($arrival.' midnight'))
            ->setDepartureDate(new \DateTimeImmutable($departure.' midnight'))
            ->setGuests(1)
            ->setTotalPrice('100.00')
            ->setStatus($status)
            ->setTraveler($traveler)
            ->setListing($this->listing);
        $this->em->persist($booking);
        $this->em->flush();

        return $booking;
    }
}
