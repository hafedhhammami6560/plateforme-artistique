<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité Message - Représente un message dans une discussion
 */
#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu du message ne peut pas être vide')]
    #[Assert\Length(
        min: 1,
        max: 5000,
        minMessage: 'Le message doit contenir au moins {{ limit }} caractère',
        maxMessage: 'Le message ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column]
    private bool $isContractProposal = false;

    #[ORM\Column]
    private bool $isRead = false;

    /**
     * L'expéditeur du message
     */
    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Un message doit avoir un expéditeur')]
    private ?User $sender = null;

    /**
     * La discussion à laquelle appartient ce message
     */
    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Un message doit appartenir à une discussion')]
    private ?Discussion $discussion = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $attachmentPath = null;

    public function __construct()
    {
        $this->sentAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function isContractProposal(): bool
    {
        return $this->isContractProposal;
    }

    public function setIsContractProposal(bool $isContractProposal): static
    {
        $this->isContractProposal = $isContractProposal;

        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setRead(bool $isRead): static
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;

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

    public function getAttachmentPath(): ?string
    {
        return $this->attachmentPath;
    }

    public function setAttachmentPath(?string $attachmentPath): static
    {
        $this->attachmentPath = $attachmentPath;

        return $this;
    }

    /**
     * Vérifie si le message a une pièce jointe
     */
    public function hasAttachment(): bool
    {
        return $this->attachmentPath !== null;
    }

    /**
     * Marque le message comme lu
     */
    public function markAsRead(): static
    {
        $this->isRead = true;

        return $this;
    }

    /**
     * Vérifie si le message a été envoyé par un utilisateur spécifique
     */
    public function isSentBy(User $user): bool
    {
        return $this->sender === $user;
    }

    /**
     * Récupère le temps écoulé depuis l'envoi du message
     */
    public function getTimeAgo(): string
    {
        $now = new \DateTimeImmutable();
        $interval = $this->sentAt->diff($now);

        if ($interval->y > 0) {
            return $interval->y . ' an' . ($interval->y > 1 ? 's' : '');
        }
        if ($interval->m > 0) {
            return $interval->m . ' mois';
        }
        if ($interval->d > 0) {
            return $interval->d . ' jour' . ($interval->d > 1 ? 's' : '');
        }
        if ($interval->h > 0) {
            return $interval->h . ' heure' . ($interval->h > 1 ? 's' : '');
        }
        if ($interval->i > 0) {
            return $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
        }

        return 'À l\'instant';
    }

    public function __toString(): string
    {
        return sprintf(
            'Message de %s - %s',
            $this->sender?->getUsername() ?? 'Inconnu',
            $this->sentAt?->format('d/m/Y H:i') ?? ''
        );
    }
}
