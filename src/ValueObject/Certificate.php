<?php

namespace App\ValueObject;

/**
 * Value Object représentant un certificat de signature électronique
 */
class Certificate
{
    private string $certificateNumber;
    private string $issuer;
    private string $subject;
    private \DateTimeImmutable $issuedAt;
    private \DateTimeImmutable $expiresAt;
    private string $publicKey;
    private string $signature;
    private array $metadata;

    public function __construct(
        string $certificateNumber,
        string $issuer,
        string $subject,
        \DateTimeImmutable $issuedAt,
        \DateTimeImmutable $expiresAt,
        string $publicKey,
        string $signature,
        array $metadata = []
    ) {
        $this->certificateNumber = $certificateNumber;
        $this->issuer = $issuer;
        $this->subject = $subject;
        $this->issuedAt = $issuedAt;
        $this->expiresAt = $expiresAt;
        $this->publicKey = $publicKey;
        $this->signature = $signature;
        $this->metadata = $metadata;
    }

    public function getCertificateNumber(): string
    {
        return $this->certificateNumber;
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getIssuedAt(): \DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isValid(): bool
    {
        $now = new \DateTimeImmutable();
        return $now >= $this->issuedAt && $now <= $this->expiresAt;
    }

    public function toArray(): array
    {
        return [
            'certificate_number' => $this->certificateNumber,
            'issuer' => $this->issuer,
            'subject' => $this->subject,
            'issued_at' => $this->issuedAt->format('Y-m-d H:i:s'),
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
            'public_key' => $this->publicKey,
            'signature' => $this->signature,
            'metadata' => $this->metadata,
            'is_valid' => $this->isValid(),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
