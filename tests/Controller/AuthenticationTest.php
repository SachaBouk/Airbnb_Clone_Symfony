<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Tests\ResetDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthenticationTest extends WebTestCase
{
    use ResetDatabaseTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $this->resetDatabase($em);

        $user = (new User())->setEmail('host@example.com')->setFirstName('Hélène')->setLastName('Hôte');
        $user->setPassword(self::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, 'password'));
        $em->persist($user);
        $em->flush();
    }

    public function testLoginReturnsTokenTtlAndUserWithoutPassword(): void
    {
        $data = $this->login('host@example.com', 'password');

        self::assertResponseIsSuccessful();
        self::assertSame(['token', 'ttl', 'user'], array_keys($data));
        self::assertNotEmpty($data['token']);
        self::assertSame(3600, $data['ttl']);
        self::assertSame('host@example.com', $data['user']['email']);
        self::assertSame(['ROLE_USER'], $data['user']['roles']);
        self::assertSame(['id', 'email', 'roles', 'firstName', 'lastName'], array_keys($data['user']));
    }

    public function testLoginWithWrongPasswordReturns401(): void
    {
        $this->login('host@example.com', 'wrong');

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetUserReturnsTheUserOfTheToken(): void
    {
        $token = $this->login('host@example.com', 'password')['token'];

        $this->client->request('GET', '/api/user', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('host@example.com', $data['email']);
        self::assertSame('Hôte', $data['lastName']);
        self::assertArrayNotHasKey('password', $data);
    }

    public function testGetUserWithoutTokenReturns401(): void
    {
        $this->client->request('GET', '/api/user');

        self::assertResponseStatusCodeSame(401);
    }

    private function login(string $email, string $password): array
    {
        $this->client->request('POST', '/api/login_check', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => $email,
            'password' => $password,
        ]));

        return json_decode($this->client->getResponse()->getContent(), true);
    }
}
