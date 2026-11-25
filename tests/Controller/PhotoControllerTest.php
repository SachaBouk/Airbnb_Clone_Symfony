<?php

namespace App\Tests\Controller;

use App\Entity\Listing;
use App\Entity\Photo;
use App\Entity\User;
use App\Service\PhotoManager;
use App\Tests\ResetDatabaseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PhotoControllerTest extends WebTestCase
{
    use ResetDatabaseTrait;

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private string $uploadDirectory;
    private User $host;
    private Listing $listing;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->resetDatabase($this->em);

        $this->uploadDirectory = self::getContainer()->getParameter('app.photo_directory');
        (new Filesystem())->remove($this->uploadDirectory);

        $this->host = $this->createUser('host@example.com');
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

    public function testHostUploadsAPhoto(): void
    {
        $this->upload($this->image('png'), as: $this->host);

        self::assertResponseStatusCodeSame(201);
        $data = $this->json();
        self::assertSame(['id', 'url'], array_keys($data));
        self::assertMatchesRegularExpression('#^/uploads/listings/[0-9a-f]{32}\.png$#', $data['url']);
        self::assertFileExists($this->uploadDirectory.'/'.basename($data['url']));

        $this->client->request('GET', '/api/listings/'.$this->listing->getId());
        self::assertSame($data['url'], $this->json()['listing']['coverUrl']);
        $photos = $this->json()['listing']['photos'];
        self::assertSame([$data['id']], array_column($photos, 'id'));
        self::assertSame([$data['url']], array_column($photos, 'url'));
    }

    public function testListShowsTheCoverOfEachListing(): void
    {
        $this->upload($this->image('jpeg'), as: $this->host);
        $url = $this->json()['url'];

        $this->client->request('GET', '/api/listings');

        self::assertSame($url, $this->json()['items'][0]['coverUrl']);
        self::assertArrayNotHasKey('photos', $this->json()['items'][0]);
    }

    public function testFileThatIsNotAnImageIsRejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fake').'.jpg';
        file_put_contents($path, 'not an image');

        $this->upload(new UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true), as: $this->host);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('photo', $this->json()['violations'][0]['propertyPath']);
        self::assertSame(0, $this->countPhotos());
    }

    public function testMissingFileIsRejected(): void
    {
        $this->client->request('POST', $this->photosUri(), server: $this->auth($this->host));

        self::assertResponseStatusCodeSame(422);
    }

    public function testOnlyTheHostCanUpload(): void
    {
        $this->upload($this->image('png'), as: $this->createUser('traveler@example.com'));
        self::assertResponseStatusCodeSame(403);

        $this->upload($this->image('png'));
        self::assertResponseStatusCodeSame(401);

        self::assertSame(0, $this->countPhotos());
    }

    public function testAListingCannotHaveMoreThanTenPhotos(): void
    {
        for ($i = 0; $i < PhotoManager::MAX_PHOTOS; ++$i) {
            $this->upload($this->image('png'), as: $this->host);
            self::assertResponseStatusCodeSame(201);
        }

        $this->upload($this->image('png'), as: $this->host);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(PhotoManager::MAX_PHOTOS, $this->countPhotos());
    }

    public function testHostDeletesAPhotoAndItsFile(): void
    {
        $this->upload($this->image('png'), as: $this->host);
        ['id' => $id, 'url' => $url] = $this->json();

        $this->client->request('DELETE', '/api/photos/'.$id, server: $this->auth($this->createUser('other@example.com')));
        self::assertResponseStatusCodeSame(403);

        $this->client->request('DELETE', '/api/photos/'.$id, server: $this->auth($this->host));
        self::assertResponseStatusCodeSame(204);
        self::assertFileDoesNotExist($this->uploadDirectory.'/'.basename($url));
        self::assertSame(0, $this->countPhotos());
    }

    public function testHostChoosesTheCover(): void
    {
        $this->upload($this->image('png'), as: $this->host);
        $first = $this->json()['id'];
        $this->upload($this->image('png'), as: $this->host);
        $second = $this->json()['id'];

        $this->client->request('POST', '/api/photos/'.$second.'/cover', server: $this->auth($this->host));

        self::assertResponseIsSuccessful();
        self::assertSame([$second, $first], array_column($this->json(), 'id'));
        $this->client->request('GET', '/api/listings/'.$this->listing->getId());
        self::assertSame([$second, $first], array_column($this->json()['listing']['photos'], 'id'));
    }

    public function testDeletingAListingDeletesItsPhotoFiles(): void
    {
        $this->upload($this->image('png'), as: $this->host);
        $file = $this->uploadDirectory.'/'.basename($this->json()['url']);

        $this->client->request('DELETE', '/api/listings/'.$this->listing->getId(), server: $this->auth($this->host));

        self::assertResponseStatusCodeSame(204);
        self::assertFileDoesNotExist($file);
        self::assertSame(0, $this->countPhotos());
    }

    private function photosUri(): string
    {
        return '/api/listings/'.$this->listing->getId().'/photos';
    }

    private function upload(UploadedFile $file, ?User $as = null): void
    {
        $this->client->request('POST', $this->photosUri(), files: ['photo' => $file], server: $as ? $this->auth($as) : []);
    }

    private function auth(User $user): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer '.self::getContainer()->get(JWTTokenManagerInterface::class)->create($user)];
    }

    private function image(string $type): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'photo');
        $image = imagecreatetruecolor(20, 20);
        'png' === $type ? imagepng($image, $path) : imagejpeg($image, $path);

        return new UploadedFile($path, 'photo.'.$type, 'image/'.$type, null, true);
    }

    private function json(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }

    private function countPhotos(): int
    {
        return $this->em->getRepository(Photo::class)->count([]);
    }

    private function createUser(string $email): User
    {
        $user = (new User())->setEmail($email)->setFirstName('Test')->setLastName('Test')->setPassword('not-a-real-hash');
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
