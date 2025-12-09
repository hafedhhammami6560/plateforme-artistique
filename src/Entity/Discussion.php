<?php

namespace App\Entity;

use App\Repository\DiscussionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DiscussionRepository::class)]
class Discussion
{
    const TYPE_PUBLICATION_RIGHTS = 'publication_rights';
    const TYPE_CUSTOM_ORDER = 'custom_order';
    
    const STATUT_EN_COURS = 'en_cours';
    const STATUT_TERMINEE = 'terminee';
    
    // Anciens statuts pour rétrocompatibilité
    const STATUT_OUVERTE = 'OUVERTE';
    const STATUT_FERMEE = 'FERMEE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 50)]
    private ?string $type = self::TYPE_PUBLICATION_RIGHTS;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sujet = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contenu = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = self::STATUT_EN_COURS;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // Soft delete - masquer pour les utilisateurs
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $hiddenByInitiateur = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $hiddenByDestinataire = false;

    // Relations
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'discussionsInitiees')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $initiateur = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'discussionsRecues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $destinataire = null;

    #[ORM\ManyToOne(targetEntity: Contrat::class, inversedBy: 'discussions')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Contrat $contrat = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Produit $produit = null;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'discussion', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $messages;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->statut = self::STATUT_EN_COURS;
        $this->type = self::TYPE_PUBLICATION_RIGHTS;
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getSujet(): ?string
    {
        return $this->sujet;
    }

    public function setSujet(string $sujet): static
    {
        $this->sujet = $sujet;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

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

    public function getInitiateur(): ?User
    {
        return $this->initiateur;
    }

    public function setInitiateur(?User $initiateur): static
    {
        $this->initiateur = $initiateur;

        return $this;
    }

    public function getDestinataire(): ?User
    {
        return $this->destinataire;
    }

    public function setDestinataire(?User $destinataire): static
    {
        $this->destinataire = $destinataire;

        return $this;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
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

    public function isTypePublicationRights(): bool
    {
        return $this->type === self::TYPE_PUBLICATION_RIGHTS;
    }

    public function isTypeCustomOrder(): bool
    {
        return $this->type === self::TYPE_CUSTOM_ORDER;
    }

    public function isTerminee(): bool
    {
        return $this->statut === self::STATUT_TERMINEE || $this->statut === self::STATUT_FERMEE;
    }

    public function isHiddenByInitiateur(): bool
    {
        return $this->hiddenByInitiateur;
    }

    public function setHiddenByInitiateur(bool $hiddenByInitiateur): static
    {
        $this->hiddenByInitiateur = $hiddenByInitiateur;
        return $this;
    }

    public function isHiddenByDestinataire(): bool
    {
        return $this->hiddenByDestinataire;
    }

    public function setHiddenByDestinataire(bool $hiddenByDestinataire): static
    {
        $this->hiddenByDestinataire = $hiddenByDestinataire;
        return $this;
    }

    /**
     * Vérifie si la discussion est masquée pour un utilisateur donné
     */
    public function isHiddenForUser(User $user): bool
    {
        if ($this->initiateur === $user) {
            return $this->hiddenByInitiateur;
        }
        if ($this->destinataire === $user) {
            return $this->hiddenByDestinataire;
        }
        return false;
    }

    /**
     * Masque la discussion pour un utilisateur donné
     */
    public function hideForUser(User $user): static
    {
        if ($this->initiateur === $user) {
            $this->hiddenByInitiateur = true;
        } elseif ($this->destinataire === $user) {
            $this->hiddenByDestinataire = true;
        }
        return $this;
    }

    /**
     * Restaure la discussion pour un utilisateur donné
     */
    public function unhideForUser(User $user): static
    {
        if ($this->initiateur === $user) {
            $this->hiddenByInitiateur = false;
        } elseif ($this->destinataire === $user) {
            $this->hiddenByDestinataire = false;
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->titre ?? 'Discussion';
    }
}
