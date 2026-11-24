<?php

namespace App\DataFixtures;

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

    private const LISTING_TYPES = [
        'Appartement lumineux', 'Studio cosy', 'Maison de charme', 'Loft moderne',
        'Villa avec piscine', 'Chalet chaleureux', 'Duplex spacieux', 'Petite maison de ville',
    ];

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $faker->seed(2026);

        // Hashing is slow on purpose: hash once and reuse it for every user.
        $hashedPassword = $this->passwordHasher->hashPassword(new User(), self::PASSWORD);

        $hosts = [$this->createUser($manager, $hashedPassword, 'host@example.com', 'Hélène', 'Hôte')];
        $travelers = [$this->createUser($manager, $hashedPassword, 'traveler@example.com', 'Victor', 'Voyageur')];

        for ($i = 1; $i < self::HOST_COUNT; ++$i) {
            $hosts[] = $this->createUser($manager, $hashedPassword, $faker->unique()->safeEmail(), $faker->firstName(), $faker->lastName());
        }
        for ($i = 1; $i < self::TRAVELER_COUNT; ++$i) {
            $travelers[] = $this->createUser($manager, $hashedPassword, $faker->unique()->safeEmail(), $faker->firstName(), $faker->lastName());
        }

        for ($i = 0; $i < self::LISTING_COUNT; ++$i) {
            $listing = $this->createListing($manager, $faker, $hosts[$i % \count($hosts)]);
            $this->createBookingsAndReviews($manager, $faker, $listing, $travelers);
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

    private function createListing(ObjectManager $manager, Generator $faker, User $host): Listing
    {
        [$city, $country] = $faker->randomElement(self::CITIES);

        $listing = (new Listing())
            ->setTitle(\sprintf('%s à %s', $faker->randomElement(self::LISTING_TYPES), $city))
            ->setDescription($faker->paragraphs(3, true))
            ->setPriceByNight(number_format($faker->numberBetween(35, 350), 2, '.', ''))
            ->setCity($city)
            ->setCountry($country)
            ->setMaxGuests($faker->numberBetween(1, 8))
            ->setHost($host);

        $manager->persist($listing);

        return $listing;
    }

    /**
     * Bookings of one listing follow each other in time, so they never overlap.
     *
     * @param list<User> $travelers
     */
    private function createBookingsAndReviews(ObjectManager $manager, Generator $faker, Listing $listing, array $travelers): void
    {
        $today = new \DateTimeImmutable('today');
        $cursor = $today->modify(\sprintf('-%d days', $faker->numberBetween(120, 200)));

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

            // Only travelers who actually stayed can leave a review.
            if ($isPast && BookingStatus::Confirmed === $status && $faker->boolean(70)) {
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
