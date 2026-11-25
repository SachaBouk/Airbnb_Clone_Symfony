<?php

namespace App\Tests\Entity;

use App\Entity\Amenity;
use App\Tests\ResetDatabaseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * createdAt / updatedAt are filled by TimestampableTrait's lifecycle callbacks.
 */
final class TimestampableTest extends KernelTestCase
{
    use ResetDatabaseTrait;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->resetDatabase($this->em);
    }

    public function testDatesAreSetOnCreationAndUpdatedAtChangesOnUpdate(): void
    {
        $amenity = (new Amenity())->setName('Wi-Fi')->setSlug('wifi');
        self::assertNull($amenity->getCreatedAt());

        $this->em->persist($amenity);
        $this->em->flush();

        $createdAt = $amenity->getCreatedAt();
        self::assertNotNull($createdAt);
        self::assertEquals($createdAt, $amenity->getUpdatedAt());

        usleep(1000);
        $amenity->setName('Wi-Fi fibre');
        $this->em->flush();

        self::assertEquals($createdAt, $amenity->getCreatedAt());
        self::assertGreaterThan($createdAt, $amenity->getUpdatedAt());
    }

    public function testDatesAreReadBackFromTheDatabase(): void
    {
        $amenity = (new Amenity())->setName('Piscine')->setSlug('pool');
        $this->em->persist($amenity);
        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->em->find(Amenity::class, $amenity->getId());

        self::assertInstanceOf(\DateTimeImmutable::class, $reloaded->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $reloaded->getUpdatedAt());
    }
}
