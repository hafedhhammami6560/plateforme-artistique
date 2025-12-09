<?php

namespace App\Entity;

use App\Repository\SignatureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SignatureRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Signature
{
    const STATUS_VALID = 'valid';
    const STATUS_REVOKED = 'revoked';
    const STATUS_EXPIRED = 'expired';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contrat::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Contrat $contrat = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $signataire = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $signatureToken = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $signatureHash = null;

    #[ORM\Column(length: 45)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $certificateData = null;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_VALID;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $revocationReason = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $signedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = self::STATUS_VALID;
    }

    #[ORM\PrePersist]
    public function setDefaultExpirationDate(): void
    {
        if ($this->expiresAt === null) {
            // Expiration par défaut: 10 ans
            $this->expiresAt = (new \DateTimeImmutable())->modify('+10 years');
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContrat(): ?Contrat
    {
        return $this->contrat;
    }

    public function setContrat(?Contrat $contrat): static
    {
        $this->contrat = $contrat;
        return $this;
    }

    public function getSignataire(): ?User
    {
        return $this->signataire;
    }

    public function setSignataire(?User $signataire): static
    {
        $this->signataire = $signataire;
        return $this;
    }

    public function getSignatureToken(): ?string
    {
        return $this->signatureToken;
    }

    public function setSignatureToken(string $signatureToken): static
    {
        $this->signatureToken = $signatureToken;
        return $this;
    }

    public function getSignatureHash(): ?string
    {
        return $this->signatureHash;
    }

    public function setSignatureHash(string $signatureHash): static
    {
        $this->signatureHash = $signatureHash;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getCertificateData(): ?string
    {
        return $this->certificateData;
    }

    public function setCertificateData(?string $certificateData): static
    {
        $this->certificateData = $certificateData;
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

    public function getRevocationReason(): ?string
    {
        return $this->revocationReason;
    }

    public function setRevocationReason(?string $revocationReason): static
    {
        $this->revocationReason = $revocationReason;
        return $this;
    }

    public function getSignedAt(): ?\DateTimeImmutable
    {
        return $this->signedAt;
    }

    public function setSignedAt(\DateTimeImmutable $signedAt): static
    {
        $this->signedAt = $signedAt;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getRevokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function setRevokedAt(?\DateTimeImmutable $revokedAt): static
    {
        $this->revokedAt = $revokedAt;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID && 
               $this->expiresAt > new \DateTimeImmutable();
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && 
               $this->expiresAt <= new \DateTimeImmutable();
    }

    public function isRevoked(): bool
    {
        return $this->status === self::STATUS_REVOKED;
    }
}
