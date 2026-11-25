<?php

namespace App\Service;

use App\Entity\Listing;
use App\Entity\Photo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final readonly class PhotoManager
{
    public const MAX_PHOTOS = 10;

    public function __construct(
        private EntityManagerInterface $em,
        private PhotoStorage $storage,
    ) {
    }

    public function add(Listing $listing, UploadedFile $file): Photo
    {
        if ($listing->getPhotos()->count() >= self::MAX_PHOTOS) {
            throw new UnprocessableEntityHttpException(\sprintf('Un logement ne peut pas avoir plus de %d photos.', self::MAX_PHOTOS));
        }

        $lastPosition = max([-1, ...$listing->getPhotos()->map(static fn (Photo $photo) => $photo->getPosition())->toArray()]);
        $filename = $this->storage->save($file);
        $photo = (new Photo())->setFilename($filename)->setPosition($lastPosition + 1);
        $listing->addPhoto($photo);

        try {
            $this->em->persist($photo);
            $this->em->flush();
        } catch (\Throwable $exception) {
            $this->storage->remove($filename);
            throw $exception;
        }

        return $photo;
    }

    public function remove(Photo $photo): void
    {
        $photo->getListing()?->removePhoto($photo);
        $this->em->remove($photo);
        $this->em->flush();
    }

    public function makeCover(Photo $photo): void
    {
        $others = $photo->getListing()->getPhotos()->filter(static fn (Photo $other) => $other !== $photo);

        $photo->setPosition(0);
        $position = 1;
        foreach ($others as $other) {
            $other->setPosition($position++);
        }

        $this->em->flush();
    }
}
