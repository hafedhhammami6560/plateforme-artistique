<?php

namespace App\Entity;

use App\Repository\ContractRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité Contract - Représente un contrat entre un artiste et un publisher
 */
#[ORM\Entity(repositoryClass: ContractRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Contract
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PROPOSED = 'proposed';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_TERMINATED = 'terminated';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Brouillon',
        self::STATUS_PROPOSED => 'Proposé',
        self::STATUS_SIGNED => 'Signé',
        self::STATUS_ACTIVE => 'Actif',
        self::STATUS_TERMINATED => 'Terminé',
    ];

    public const COMMISSION_RATES = [
        '10' => '10% - Standard',
        '15' => '15% - Intermédiaire',
        '20' => '20% - Premium',
        '25' => '25% - Exclusif',
        '0' => 'Négociable',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Les termes du contrat sont obligatoires')]
    #[Assert\Length(
        min: 50,
        minMessage: 'Les termes doivent contenir au moins {{ limit }} caractères'
    )]
    private ?string $terms = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotNull(message: 'Le taux de commission est obligatoire')]
    #[Assert\Range(
        min: 0,
        max: 100,
        notInRangeMessage: 'Le taux de commission doit être entre {{ min }}% et {{ max }}%'
    )]
    private ?string $commissionRate = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(
        choices: [self::STATUS_DRAFT, self::STATUS_PROPOSED, self::STATUS_SIGNED, self::STATUS_ACTIVE, self::STATUS_TERMINATED],
        message: 'Statut invalide'
    )]
    private ?string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'La date de début est obligatoire')]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'La date de fin est obligatoire')]
    #[Assert\GreaterThan(
        propertyPath: 'startDate',
        message: 'La date de fin doit être postérieure à la date de début'
    )]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $signedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * La discussion qui a mené à ce contrat
     */
    #[ORM\OneToOne(inversedBy: 'contract')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Un contrat doit être lié à une discussion')]
    private ?Discussion $discussion = null;

    /**
     * L'utilisateur qui a signé le contrat (généralement l'artiste)
     */
    #[ORM\ManyToOne(inversedBy: 'signedContracts')]
    private ?User $signedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $referenceNumber = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->generateReferenceNumber();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function generateReferenceNumber(): void
    {
        $this->referenceNumber = 'CNT-' . strtoupper(substr(md5(uniqid()), 0, 10));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTerms(): ?string
    {
        return $this->terms;
    }

    public function setTerms(string $terms): static
    {
        $this->terms = $terms;

        return $this;
    }

    public function getCommissionRate(): ?string
    {
        return $this->commissionRate;
    }

    public function setCommissionRate(string $commissionRate): static
    {
        $this->commissionRate = $commissionRate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getSignedAt(): ?\DateTimeInterface
    {
        return $this->signedAt;
    }

    public function setSignedAt(?\DateTimeInterface $signedAt): static
    {
        $this->signedAt = $signedAt;

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

    public function getDiscussion(): ?Discussion
    {
        return $this->discussion;
    }

    public function setDiscussion(?Discussion $discussion): static
    {
        $this->discussion = $discussion;

        return $this;
    }

    public function getSignedBy(): ?User
    {
        return $this->signedBy;
    }

    public function setSignedBy(?User $signedBy): static
    {
        $this->signedBy = $signedBy;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
    }

    public function setReferenceNumber(?string $referenceNumber): static
    {
        $this->referenceNumber = $referenceNumber;

        return $this;
    }

    /**
     * Signe le contrat
     */
    public function sign(User $user): static
    {
        $this->signedBy = $user;
        $this->signedAt = new \DateTime();
        $this->status = self::STATUS_SIGNED;

        return $this;
    }

    /**
     * Vérifie si le contrat est signé
     */
    public function isSigned(): bool
    {
        return $this->status === self::STATUS_SIGNED || $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Vérifie si le contrat est actif
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Vérifie si le contrat est terminé
     */
    public function isTerminated(): bool
    {
        return $this->status === self::STATUS_TERMINATED;
    }

    /**
     * Vérifie si le contrat peut être signé
     */
    public function canBeSigned(): bool
    {
        return $this->status === self::STATUS_PROPOSED && !$this->isSigned();
    }

    /**
     * Vérifie si le contrat expire bientôt (dans les 30 jours)
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->endDate) {
            return false;
        }

        $now = new \DateTime();
        $interval = $now->diff($this->endDate);
        
        return $interval->days <= 30 && $interval->invert === 0;
    }

    /**
     * Vérifie si le contrat est expiré
     */
    public function isExpired(): bool
    {
        if (!$this->endDate) {
            return false;
        }

        $now = new \DateTime();
        return $now > $this->endDate;
    }

    /**
     * Calcule la durée du contrat en jours
     */
    public function getDurationInDays(): int
    {
        if (!$this->startDate || !$this->endDate) {
            return 0;
        }

        $interval = $this->startDate->diff($this->endDate);
        return $interval->days;
    }

    /**
     * Récupère l'artiste via la discussion
     */
    public function getArtist(): ?User
    {
        return $this->discussion?->getArtist();
    }

    /**
     * Récupère le publisher via la discussion
     */
    public function getPublisher(): ?User
    {
        return $this->discussion?->getPublisher();
    }

    /**
     * Récupère le produit via la discussion
     */
    public function getProduct(): ?Product
    {
        return $this->discussion?->getProduct();
    }

    public function __toString(): string
    {
        return sprintf(
            'Contrat %s - Commission: %s%%',
            $this->referenceNumber ?? '#' . ($this->id ?? 'N/A'),
            $this->commissionRate
        );
    }
}
