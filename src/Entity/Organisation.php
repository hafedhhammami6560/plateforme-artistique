<?php

namespace App\Entity;

use App\Repository\OrganisationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Organisation - Représente une structure artistique ou culturelle
 */
#[ORM\Entity(repositoryClass: OrganisationRepository::class)]
#[ORM\Table(name: 'organisation')]
class Organisation
{
    #[ORM\Column(type: 'string', length: 50)]
    private ?string $type = null;

    public function getType(): ?string { return $this->type; }
    public function setType(?string $type): static { $this->type = $type; return $this; }
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $addressGoogle = null;


    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: Communite::class, inversedBy: 'organisations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Communite $communite = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->createdBy = null;
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getAddressGoogle(): ?string { return $this->addressGoogle; }
    public function setAddressGoogle(?string $addressGoogle): static { $this->addressGoogle = $addressGoogle; return $this; }
    public function getLatitude(): ?float { return $this->latitude; }
    public function setLatitude(?float $latitude): static { $this->latitude = $latitude; return $this; }
    public function getLongitude(): ?float { return $this->longitude; }
    public function setLongitude(?float $longitude): static { $this->longitude = $longitude; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }
    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(User $createdBy): static { $this->createdBy = $createdBy; return $this; }
    public function getCommunite(): ?Communite { return $this->communite; }
    public function setCommunite(?Communite $communite): static { $this->communite = $communite; return $this; }
    public function __toString(): string { return $this->name ?? ''; }
}
