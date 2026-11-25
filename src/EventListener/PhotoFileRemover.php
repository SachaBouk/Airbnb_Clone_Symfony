<?php

namespace App\EventListener;

use App\Entity\Photo;
use App\Service\PhotoStorage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

/**
 * Deletes the file of a photo once its row is deleted, whatever the reason:
 * DELETE /api/photos/{id}, or the deletion of its listing (cascade).
 */
#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: Photo::class)]
final readonly class PhotoFileRemover
{
    public function __construct(
        private PhotoStorage $storage,
    ) {
    }

    public function postRemove(Photo $photo): void
    {
        $this->storage->remove($photo->getFilename());
    }
}
