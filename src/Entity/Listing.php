<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\ListingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ListingRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Listing
{
    use TimestampableTrait;

    #[Groups(['listing:list', 'listing:read', 'review:feed'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['listing:list', 'listing:read', 'review:feed'])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[Groups(['listing:read'])]
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $description = null;

    #[Groups(['listing:list', 'listing:read'])]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $priceByNight = null;

    #[Groups(['listing:list', 'listing:read'])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $city = null;

    #[Groups(['listing:list', 'listing:read'])]
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $country = null;

    #[Groups(['listing:list', 'listing:read'])]
    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?int $maxGuests = null;

    #[Groups(['listing:list', 'listing:read'])]
    #[ORM\ManyToOne(inversedBy: 'listings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?User $host = null;

    /**
     * @var Collection<int, Amenity>
     */
    #[ORM\ManyToMany(targetEntity: Amenity::class, inversedBy: 'listings')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $amenities;

    public function __construct()
    {
        $this->amenities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Amenity>
     */
    public function getAmenities(): Collection
    {
        return $this->amenities;
    }

    /**
     * @return list<Amenity>
     */
    #[Groups(['listing:list', 'listing:read'])]
    #[SerializedName('amenities')]
    public function getAmenityList(): array
    {
        return array_values($this->amenities->toArray());
    }

    public function addAmenity(Amenity $amenity): static
    {
        if (!$this->amenities->contains($amenity)) {
            $this->amenities->add($amenity);
            $amenity->addListing($this);
        }

        return $this;
    }

    public function removeAmenity(Amenity $amenity): static
    {
        if ($this->amenities->removeElement($amenity)) {
            $amenity->removeListing($this);
        }

        return $this;
    }

    /**
     * @param iterable<Amenity> $amenities
     */
    public function replaceAmenities(iterable $amenities): static
    {
        foreach ($this->amenities->toArray() as $amenity) {
            $this->removeAmenity($amenity);
        }
        foreach ($amenities as $amenity) {
            $this->addAmenity($amenity);
        }

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPriceByNight(): ?string
    {
        return $this->priceByNight;
    }

    public function setPriceByNight(string $priceByNight): static
    {
        $this->priceByNight = $priceByNight;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getMaxGuests(): ?int
    {
        return $this->maxGuests;
    }

    public function setMaxGuests(int $maxGuests): static
    {
        $this->maxGuests = $maxGuests;

        return $this;
    }

    public function getHost(): ?User
    {
        return $this->host;
    }

    public function setHost(User $host): static
    {
        $this->host = $host;

        return $this;
    }
}
