<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\ResetDatabaseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserFollowTest extends WebTestCase
{
    use ResetDatabaseTrait;

    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->resetDatabase($this->em);
    }

    public function testFollowAddsTheUserToFollowersAndFollowing(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        $traveler = $this->createUser('traveler@example.com', 'Victor');
        $this->em->flush();

        $this->request('POST', '/api/users/'.$host->getId().'/follow', as: $traveler);
        self::assertResponseStatusCodeSame(204);

        $this->request('GET', '/api/users/'.$host->getId().'/followers');
        self::assertResponseIsSuccessful();
        $followers = $this->json();
        self::assertSame(['total', 'lastPage', 'items'], array_keys($followers));
        self::assertSame(1, $followers['total']);
        self::assertSame([['id' => $traveler->getId(), 'firstName' => 'Victor']], $followers['items']);

        $this->request('GET', '/api/users/'.$traveler->getId().'/following');
        self::assertSame([$host->getId()], array_column($this->json()['items'], 'id'));
    }

    public function testFollowingTwiceIsIdempotent(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        $traveler = $this->createUser('traveler@example.com', 'Victor');
        $this->em->flush();

        $this->request('POST', '/api/users/'.$host->getId().'/follow', as: $traveler);
        $this->request('POST', '/api/users/'.$host->getId().'/follow', as: $traveler);

        self::assertResponseStatusCodeSame(204);
        $this->request('GET', '/api/users/'.$host->getId().'/followers');
        self::assertSame(1, $this->json()['total']);
    }

    public function testUnfollowRemovesTheLink(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        $traveler = $this->createUser('traveler@example.com', 'Victor')->follow($host);
        $this->em->flush();

        $this->request('DELETE', '/api/users/'.$host->getId().'/follow', as: $traveler);

        self::assertResponseStatusCodeSame(204);
        $this->request('GET', '/api/users/'.$host->getId().'/followers');
        self::assertSame(0, $this->json()['total']);
    }

    public function testCannotFollowYourself(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        $this->em->flush();

        $this->request('POST', '/api/users/'.$host->getId().'/follow', as: $host);

        self::assertResponseStatusCodeSame(422);
        $this->request('GET', '/api/users/'.$host->getId().'/followers');
        self::assertSame(0, $this->json()['total']);
    }

    public function testFollowWithoutTokenReturns401(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        $this->em->flush();

        $this->request('POST', '/api/users/'.$host->getId().'/follow');

        self::assertResponseStatusCodeSame(401);
    }

    public function testFollowUnknownUserReturns404(): void
    {
        $traveler = $this->createUser('traveler@example.com', 'Victor');
        $this->em->flush();

        $this->request('POST', '/api/users/999999/follow', as: $traveler);

        self::assertResponseStatusCodeSame(404);
    }

    public function testFollowersListIsPaginated(): void
    {
        $host = $this->createUser('host@example.com', 'Hélène');
        for ($i = 1; $i <= 5; ++$i) {
            $this->createUser("fan$i@example.com", "Fan$i")->follow($host);
        }
        $this->em->flush();

        $this->request('GET', '/api/users/'.$host->getId().'/followers?page=3&limit=2');

        self::assertResponseIsSuccessful();
        $data = $this->json();
        self::assertSame(5, $data['total']);
        self::assertSame(3, $data['lastPage']);
        self::assertSame(['Fan5'], array_column($data['items'], 'firstName'));

        $this->request('GET', '/api/users/'.$host->getId().'/followers?limit=0');
        self::assertResponseStatusCodeSame(422);
    }

    private function request(string $method, string $uri, ?User $as = null): void
    {
        $server = [];
        if (null !== $as) {
            $token = self::getContainer()->get(JWTTokenManagerInterface::class)->create($as);
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        $this->client->request($method, $uri, server: $server);
    }

    private function json(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }

    private function createUser(string $email, string $firstName): User
    {
        $user = (new User())->setEmail($email)->setFirstName($firstName)->setLastName('Test')->setPassword('not-a-real-hash');
        $this->em->persist($user);

        return $user;
    }
}
