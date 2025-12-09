<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $prix = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'produits')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Categorie $categorie = null;

    // Libellé de catégorie (compatibilité scripts de seed)
    #[ORM\Column(name: 'categorie', length: 255, nullable: true)]
    private ?string $categorieLabel = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $artist = null;

    #[ORM\Column]
    private bool $sousContrat = false;

    #[ORM\Column(length: 50)]
    private ?string $statut = 'disponible';

    // Relation avec Contrat - One to One
    #[ORM\OneToOne(targetEntity: Contrat::class, mappedBy: 'produit')]
    private ?Contrat $contrat = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
        $this->sousContrat = false;
        $this->statut = 'disponible';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getCategorieLabel(): ?string
    {
        return $this->categorieLabel;
    }

    public function setCategorieLabel(?string $categorieLabel): static
    {
        $this->categorieLabel = $categorieLabel;
        return $this;
    }

    public function getArtist(): ?User
    {
        return $this->artist;
    }

    public function setArtist(?User $artist): static
    {
        $this->artist = $artist;

        return $this;
    }

    public function isSousContrat(): bool
    {
        return $this->sousContrat;
    }

    public function setSousContrat(bool $sousContrat): static
    {
        $this->sousContrat = $sousContrat;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getContrat(): ?Contrat
    {
        return $this->contrat;
    }

    public function setContrat(?Contrat $contrat): static
    {
        // Unset the owning side of the relation if necessary
        if ($contrat === null && $this->contrat !== null) {
            $this->contrat->setProduit(null);
        }

        // Set the owning side of the relation if necessary
        if ($contrat !== null && $contrat->getProduit() !== $this) {
            $contrat->setProduit($this);
        }

        $this->contrat = $contrat;

        return $this;
    }

    public function isDisponible(): bool
    {
        return $this->statut === 'disponible' && !$this->sousContrat;
    }

    public function marquerSousContrat(Contrat $contrat): static
    {
        $this->sousContrat = true;
        $this->contrat = $contrat;
        $this->statut = 'sous_contrat';

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? 'Produit';
    }
}
