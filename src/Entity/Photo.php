<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\PhotoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PhotoRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Photo
{
    use TimestampableTrait;

    public const PUBLIC_PATH = '/uploads/listings';

    #[Groups(['listing:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

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
