<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableTrait;

    #[Groups(['user:public', 'user:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['user:read'])]
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[Groups(['user:read'])]
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[Groups(['user:public', 'user:read'])]
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $firstName = null;

    #[Groups(['user:read'])]
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $lastName = null;

    #[ORM\OneToMany(targetEntity: Listing::class, mappedBy: 'host')]
    private Collection $listings;

    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'author')]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $reviews;

    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'followers')]
    #[ORM\JoinTable(name: 'user_follow')]
    #[ORM\JoinColumn(name: 'follower_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'followed_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $following;

    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'following')]
    private Collection $followers;

    public function __construct()
    {
        $this->listings = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->following = new ArrayCollection();
        $this->followers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getListings(): Collection
    {
        return $this->listings;
    }

    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function getFollowing(): Collection
    {
        return $this->following;
    }

    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function isFollowing(self $user): bool
    {
        return $this->following->contains($user);
    }

    public function follow(self $user): static
    {
        if (!$this->following->contains($user)) {
            $this->following->add($user);
            $user->followers->add($this);
        }

        return $this;
    }

    public function unfollow(self $user): static
    {
        if ($this->following->removeElement($user)) {
            $user->followers->removeElement($this);
        }

        return $this;
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }
}
