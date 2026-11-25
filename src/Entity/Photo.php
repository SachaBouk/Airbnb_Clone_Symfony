<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\PhotoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * A photo of a listing. The file lives in public/uploads/listings/, only its name is stored here.
 */
#[ORM\Entity(repositoryClass: PhotoRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Photo
{
    use TimestampableTrait;

    /** Public URL prefix of the photo files (under the public/ directory). */
    public const PUBLIC_PATH = '/uploads/listings';

    #[Groups(['listing:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Unique file name generated at upload, e.g. "3f9a…c2.jpg". */
    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    /** Order in the gallery: the photo with the lowest position is the cover. */
    #[ORM\Column]
    private int $position = 0;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Listing $listing = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    #[Groups(['listing:read'])]
    public function getUrl(): string
    {
        return self::PUBLIC_PATH.'/'.$this->filename;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getListing(): ?Listing
    {
        return $this->listing;
    }

    public function setListing(Listing $listing): static
    {
        $this->listing = $listing;

        return $this;
    }
}
