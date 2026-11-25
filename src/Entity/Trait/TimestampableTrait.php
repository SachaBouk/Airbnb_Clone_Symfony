<?php

namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

trait TimestampableTrait
{
    #[Groups(['all:read'])]
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[Groups(['all:read'])]
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt ??= new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
