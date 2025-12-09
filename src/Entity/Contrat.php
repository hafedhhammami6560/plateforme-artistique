<?php

namespace App\Entity;

use App\Repository\ContratRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContratRepository::class)]
class Contrat
{
    const TYPE_PUBLICATION_RIGHTS = 'publication_rights';
    const TYPE_CUSTOM_ORDER = 'custom_order';
    
    const STATUT_BROUILLON = 'brouillon';
    const STATUT_EN_ATTENTE_SIGNATURE = 'en_attente_signature';
    const STATUT_SIGNE = 'signe';
    const STATUT_FINAL = 'final'; // Contrat final prêt à signer
    
    // Anciens statuts pour rétrocompatibilité
    const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    const STATUT_ACCEPTE = 'ACCEPTE';
    const STATUT_ANNULE = 'ANNULE';
    const STATUT_ACTIF = 'ACTIF';
    const STATUT_TERMINE = 'TERMINE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $numeroContrat = null;

    #[ORM\Column(length: 50)]
    private ?string $type = self::TYPE_PUBLICATION_RIGHTS;

    #[ORM\Column]
    private ?float $montant = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prix = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $conditionsTexte = null;

    #[ORM\Column]
    private bool $signatureArtist = false;

    #[ORM\Column]
    private bool $signatureClient = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateSignature = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateSignatureArtist = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateSignatureClient = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $termes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $documentFile = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // Relations
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'contratsAsProducteur')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $producteur = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'contratsAsArtiste')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $artiste = null;

    #[ORM\OneToOne(targetEntity: Produit::class, inversedBy: 'contrat')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Produit $produit = null;

    #[ORM\OneToMany(targetEntity: Discussion::class, mappedBy: 'contrat', cascade: ['remove'])]
    private Collection $discussions;
    
    // Discussion d'origine pour les brouillons de contrats
    #[ORM\ManyToOne(targetEntity: Discussion::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Discussion $discussionOrigine = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->statut = self::STATUT_BROUILLON;
        $this->type = self::TYPE_PUBLICATION_RIGHTS;
        $this->discussions = new ArrayCollection();
        $this->signatureArtist = false;
        $this->signatureClient = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;

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

    public function getTermes(): ?string
    {
        return $this->termes;
    }

    public function setTermes(string $termes): static
    {
        $this->termes = $termes;

        return $this;
    }

    public function getDocumentFile(): ?string
    {
        return $this->documentFile;
    }

    public function setDocumentFile(?string $documentFile): static
    {
        $this->documentFile = $documentFile;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getProducteur(): ?User
    {
        return $this->producteur;
    }

    public function setProducteur(?User $producteur): static
    {
        $this->producteur = $producteur;

        return $this;
    }

    public function getArtiste(): ?User
    {
        return $this->artiste;
    }

    public function setArtiste(?User $artiste): static
    {
        $this->artiste = $artiste;

        return $this;
    }

    public function getNumeroContrat(): ?string
    {
        return $this->numeroContrat;
    }

    public function setNumeroContrat(string $numeroContrat): static
    {
        $this->numeroContrat = $numeroContrat;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getConditionsTexte(): ?string
    {
        return $this->conditionsTexte;
    }

    public function setConditionsTexte(string $conditionsTexte): static
    {
        $this->conditionsTexte = $conditionsTexte;

        return $this;
    }

    public function isSignatureArtist(): bool
    {
        return $this->signatureArtist;
    }

    public function setSignatureArtist(bool $signatureArtist): static
    {
        $this->signatureArtist = $signatureArtist;
        if ($signatureArtist && !$this->dateSignatureArtist) {
            $this->dateSignatureArtist = new \DateTimeImmutable();
        }

        return $this;
    }

    public function isSignatureClient(): bool
    {
        return $this->signatureClient;
    }

    public function setSignatureClient(bool $signatureClient): static
    {
        $this->signatureClient = $signatureClient;
        if ($signatureClient && !$this->dateSignatureClient) {
            $this->dateSignatureClient = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getDateSignature(): ?\DateTimeImmutable
    {
        return $this->dateSignature;
    }

    public function setDateSignature(?\DateTimeImmutable $dateSignature): static
    {
        $this->dateSignature = $dateSignature;

        return $this;
    }

    public function getDateSignatureArtist(): ?\DateTimeImmutable
    {
        return $this->dateSignatureArtist;
    }

    public function getDateSignatureClient(): ?\DateTimeImmutable
    {
        return $this->dateSignatureClient;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;

        return $this;
    }

    public function isFullySigned(): bool
    {
        return $this->signatureArtist && $this->signatureClient;
    }

    public function canBeModified(): bool
    {
        return !$this->signatureArtist && !$this->signatureClient;
    }

    public function isTypePublicationRights(): bool
    {
        return $this->type === self::TYPE_PUBLICATION_RIGHTS;
    }

    public function isTypeCustomOrder(): bool
    {
        return $this->type === self::TYPE_CUSTOM_ORDER;
    }

    /**
     * @return Collection<int, Discussion>
     */
    public function getDiscussions(): Collection
    {
        return $this->discussions;
    }

    public function addDiscussion(Discussion $discussion): static
    {
        if (!$this->discussions->contains($discussion)) {
            $this->discussions->add($discussion);
            $discussion->setContrat($this);
        }

        return $this;
    }

    public function removeDiscussion(Discussion $discussion): static
    {
        if ($this->discussions->removeElement($discussion)) {
            if ($discussion->getContrat() === $this) {
                $discussion->setContrat(null);
            }
        }

        return $this;
    }

    public function getDiscussionOrigine(): ?Discussion
    {
        return $this->discussionOrigine;
    }

    public function setDiscussionOrigine(?Discussion $discussionOrigine): static
    {
        $this->discussionOrigine = $discussionOrigine;

        return $this;
    }

    public function __toString(): string
    {
        return $this->numeroContrat ?? 'Contrat #' . $this->id;
    }
}
