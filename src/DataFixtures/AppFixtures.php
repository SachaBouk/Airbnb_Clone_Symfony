<?php

namespace App\DataFixtures;

use App\Entity\Amenity;
use App\Entity\Booking;
use App\Entity\Listing;
use App\Entity\Review;
use App\Entity\User;
use App\Enum\BookingStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public const PASSWORD = 'password';

    private const HOST_COUNT = 10;
    private const TRAVELER_COUNT = 20;
    private const LISTING_COUNT = 25;

    private const CITIES = [
        ['Paris', 'France'], ['Lyon', 'France'], ['Marseille', 'France'], ['Bordeaux', 'France'],
        ['Nice', 'France'], ['Annecy', 'France'], ['Biarritz', 'France'], ['Lisbonne', 'Portugal'],
        ['Porto', 'Portugal'], ['Barcelone', 'Espagne'], ['Madrid', 'Espagne'], ['Rome', 'Italie'],
        ['Florence', 'Italie'], ['Berlin', 'Allemagne'], ['Amsterdam', 'Pays-Bas'], ['Bruxelles', 'Belgique'],
    ];

    /** slug => name */
    private const AMENITIES = [
        'wifi' => 'Wi-Fi', 'kitchen' => 'Cuisine équipée', 'parking' => 'Parking gratuit',
        'pool' => 'Piscine', 'air-conditioning' => 'Climatisation', 'washing-machine' => 'Lave-linge',
        'tv' => 'Télévision', 'workspace' => 'Espace de travail', 'pets-allowed' => 'Animaux acceptés',
        'balcony' => 'Balcon', 'sea-view' => 'Vue sur mer', 'fireplace' => 'Cheminée',
    ];

    private const LISTING_TYPES = [
        'Appartement lumineux', 'Studio cosy', 'Maison de charme', 'Loft moderne',
        'Villa avec piscine', 'Chalet chaleureux', 'Duplex spacieux', 'Petite maison de ville',
    ];

    /** @var list<User> travelers who wrote at least one review */
    private array $reviewers = [];

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $faker->seed(2026);
        $this->reviewers = [];

        $hashedPassword = $this->passwordHasher->hashPassword(new User(), self::PASSWORD);

        $hosts = [$this->createUser($manager, $hashedPassword, 'host@example.com', 'Hélène', 'Hôte')];
        $travelers = [$this->createUser($manager, $hashedPassword, 'traveler@example.com', 'Victor', 'Voyageur')];

        for ($i = 1; $i < self::HOST_COUNT; ++$i) {
            $hosts[] = $this->createUser($manager, $hashedPassword, $faker->unique()->safeEmail(), $faker->firstName(), $faker->lastName());
        }
        for ($i = 1; $i < self::TRAVELER_COUNT; ++$i) {
            $travelers[] = $this->createUser($manager, $hashedPassword, $faker->unique()->safeEmail(), $faker->firstName(), $faker->lastName());
        }

        $amenities = [];
        foreach (self::AMENITIES as $slug => $name) {
            $amenities[] = $amenity = (new Amenity())->setSlug($slug)->setName($name);
            $manager->persist($amenity);
        }

        for ($i = 0; $i < self::LISTING_COUNT; ++$i) {
            $listing = $this->createListing($manager, $faker, $hosts[$i % \count($hosts)], $amenities);
            // traveler@example.com never reviews the first listing: see the demo stay below.
            $this->createBookingsAndReviews($manager, $faker, $listing, $travelers, 0 === $i ? [$travelers[0]] : []);

            if (0 === $i) {
                $this->createDemoStay($manager, $listing, $travelers[0]);
            }
        }

        // Self-referencing ManyToMany: travelers follow hosts and other travelers, hosts follow each other.
        foreach ($travelers as $traveler) {
            foreach ($faker->randomElements($hosts, $faker->numberBetween(0, 3)) as $host) {
                $traveler->follow($host);
            }
            foreach ($faker->randomElements($travelers, $faker->numberBetween(0, 3)) as $otherTraveler) {
                if ($otherTraveler !== $traveler) {
                    $traveler->follow($otherTraveler);
                }
            }
        }
        // traveler@example.com follows some reviewers, so that GET /api/feed has content in api.http.
        $reviewers = [];
        foreach ($this->reviewers as $reviewer) {
            if ($reviewer !== $travelers[0]) {
                $reviewers[spl_object_id($reviewer)] = $reviewer;
            }
        }
        foreach (\array_slice($reviewers, 0, 4) as $reviewer) {
            $travelers[0]->follow($reviewer);
        }
        foreach ($hosts as $host) {
            foreach ($faker->randomElements($hosts, $faker->numberBetween(0, 2)) as $otherHost) {
                if ($otherHost !== $host) {
                    $host->follow($otherHost);
                }
            }
        }

        $manager->flush();
    }

    private function createUser(ObjectManager $manager, string $hashedPassword, string $email, string $firstName, string $lastName): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setPassword($hashedPassword);

        $manager->persist($user);

        return $user;
    }

    /**
     * @param list<Amenity> $amenities
     */
    private function createListing(ObjectManager $manager, Generator $faker, User $host, array $amenities): Listing
    {
        [$city, $country] = $faker->randomElement(self::CITIES);

        $listing = (new Listing())
            ->setTitle(\sprintf('%s à %s', $faker->randomElement(self::LISTING_TYPES), $city))
            ->setDescription($faker->paragraphs(3, true))
            ->setPriceByNight(number_format($faker->numberBetween(35, 350), 2, '.', ''))
            ->setCity($city)
            ->setCountry($country)
            ->setMaxGuests($faker->numberBetween(1, 8))
            ->setHost($host)
            ->replaceAmenities($faker->randomElements($amenities, $faker->numberBetween(2, 6)));

        $manager->persist($listing);

        return $listing;
    }

    /**
     * A finished, confirmed stay of traveler@example.com at the first listing (host@example.com),
     * without review: lets api.http demo POST /api/listings/{id}/reviews.
     * 120 days ago, so it never overlaps the generated bookings (they start at most 90 days ago).
     */
    private function createDemoStay(ObjectManager $manager, Listing $listing, User $traveler): void
    {
        $arrival = new \DateTimeImmutable('-120 days midnight');

        $manager->persist((new Booking())
            ->setArrivalDate($arrival)
            ->setDepartureDate($arrival->modify('+3 days'))
            ->setGuests(1)
            ->setTotalPrice(number_format(3 * (float) $listing->getPriceByNight(), 2, '.', ''))
            ->setStatus(BookingStatus::Confirmed)
            ->setTraveler($traveler)
            ->setListing($listing));
    }

    /**
     * @param list<User> $travelers
     * @param list<User> $neverReviewing travelers who must not get a generated review on this listing
     */
    private function createBookingsAndReviews(ObjectManager $manager, Generator $faker, Listing $listing, array $travelers, array $neverReviewing = []): void
    {
        // One review per traveler per listing (enforced by a unique constraint on Review).
        $reviewed = $neverReviewing;

        $today = new \DateTimeImmutable('today');
        $cursor = $today->modify(\sprintf('-%d days', $faker->numberBetween(30, 90)));

        for ($i = 0, $count = $faker->numberBetween(0, 5); $i < $count; ++$i) {
            $arrival = $cursor->modify(\sprintf('+%d days', $faker->numberBetween(3, 40)));
            $nights = $faker->numberBetween(1, 10);
            $departure = $arrival->modify(\sprintf('+%d days', $nights));
            $cursor = $departure;

            $isPast = $departure < $today;
            $status = $isPast
                ? $faker->randomElement([BookingStatus::Confirmed, BookingStatus::Confirmed, BookingStatus::Cancelled])
                : $faker->randomElement([BookingStatus::Pending, BookingStatus::Confirmed]);

            $traveler = $faker->randomElement($travelers);

            $booking = (new Booking())
                ->setArrivalDate($arrival)
                ->setDepartureDate($departure)
                ->setGuests($faker->numberBetween(1, $listing->getMaxGuests()))
                ->setTotalPrice(number_format($nights * (float) $listing->getPriceByNight(), 2, '.', ''))
                ->setStatus($status)
                ->setTraveler($traveler)
                ->setListing($listing);

            $manager->persist($booking);

            if ($isPast && BookingStatus::Confirmed === $status && !\in_array($traveler, $reviewed, true) && $faker->boolean(70)) {
                $reviewed[] = $traveler;
                $this->reviewers[] = $traveler;
                $review = (new Review())
                    ->setRating($faker->biasedNumberBetween(1, 5, 'sqrt'))
                    ->setComment($faker->paragraph())
                    ->setAuthor($traveler)
                    ->setListing($listing);

                $manager->persist($review);
            }
        }
    }
}
