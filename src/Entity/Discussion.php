<?php

namespace App\Entity;

use App\Repository\DiscussionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité Discussion - Représente une conversation entre un artiste et un publisher
 * concernant un produit spécifique
 */
#[ORM\Entity(repositoryClass: DiscussionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Discussion
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_PENDING => 'En attente',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_CLOSED => 'Fermée',
        self::STATUS_ARCHIVED => 'Archivée',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(
        choices: [self::STATUS_PENDING, self::STATUS_ACTIVE, self::STATUS_CLOSED, self::STATUS_ARCHIVED],
        message: 'Statut invalide'
    )]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * L'artiste participant à la discussion
     */
    #[ORM\ManyToOne(inversedBy: 'artistDiscussions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Un artiste doit être spécifié')]
    private ?User $artist = null;

    /**
     * Le publisher qui a initié la discussion
     */
    #[ORM\ManyToOne(inversedBy: 'publisherDiscussions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Un publisher doit être spécifié')]
    private ?User $publisher = null;

    /**
     * Le produit concerné par la discussion
     */
    #[ORM\ManyToOne(inversedBy: 'discussions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Un produit doit être spécifié')]
    private ?Product $product = null;

    /**
     * @var Collection<int, Message>
     * Tous les messages de cette discussion
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'discussion', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sentAt' => 'ASC'])]
    private Collection $messages;

    /**
     * Le contrat lié à cette discussion (si accord trouvé)
     */
    #[ORM\OneToOne(mappedBy: 'discussion', cascade: ['persist', 'remove'])]
    private ?Contract $contract = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $subject = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getArtist(): ?User
    {
        return $this->artist;
    }

    public function setArtist(?User $artist): static
    {
        $this->artist = $artist;

        return $this;
    }

    public function getPublisher(): ?User
    {
        return $this->publisher;
    }

    public function setPublisher(?User $publisher): static
    {
        $this->publisher = $publisher;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setDiscussion($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getDiscussion() === $this) {
                $message->setDiscussion(null);
            }
        }

        return $this;
    }

    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    public function setContract(?Contract $contract): static
    {
        // unset the owning side of the relation if necessary
        if ($contract === null && $this->contract !== null) {
            $this->contract->setDiscussion(null);
        }

        // set the owning side of the relation if necessary
        if ($contract !== null && $contract->getDiscussion() !== $this) {
            $contract->setDiscussion($this);
        }

        $this->contract = $contract;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Récupère le dernier message de la discussion
     */
    public function getLastMessage(): ?Message
    {
        if ($this->messages->isEmpty()) {
            return null;
        }

        $messagesArray = $this->messages->toArray();
        return end($messagesArray);
    }

    /**
     * Compte le nombre de messages
     */
    public function getMessageCount(): int
    {
        return $this->messages->count();
    }

    /**
     * Vérifie si la discussion est active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Vérifie si la discussion est fermée
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Vérifie si un utilisateur est participant de la discussion
     */
    public function isParticipant(User $user): bool
    {
        return $this->artist === $user || $this->publisher === $user;
    }

    /**
     * Récupère l'autre participant (pas l'utilisateur courant)
     */
    public function getOtherParticipant(User $currentUser): ?User
    {
        if ($this->artist === $currentUser) {
            return $this->publisher;
        }
        
        if ($this->publisher === $currentUser) {
            return $this->artist;
        }

        return null;
    }

    /**
     * Vérifie si la discussion a un contrat
     */
    public function hasContract(): bool
    {
        return $this->contract !== null;
    }

    public function __toString(): string
    {
        return sprintf(
            'Discussion #%d - %s',
            $this->id ?? 0,
            $this->subject ?? 'Sans sujet'
        );
    }
}
