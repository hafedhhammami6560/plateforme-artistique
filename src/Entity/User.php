<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Entité User - Représente les utilisateurs de la plateforme ArtConnect
 * Types : Artiste (ROLE_ARTIST) ou Publisher (ROLE_PUBLISHER)
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un compte avec cet email')]
#[UniqueEntity(fields: ['username'], message: 'Ce nom d\'utilisateur est déjà pris')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $username = null;

    /**
     * @var list<string> Les rôles de l'utilisateur
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string Le mot de passe hashé
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * Type d'utilisateur : artist ou publisher
     */
    #[ORM\Column(length: 20)]
    private ?string $type = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'artist', cascade: ['persist', 'remove'])]
    private Collection $products;

    /**
     * @var Collection<int, Discussion>
     * Discussions initiées en tant que publisher
     */
    #[ORM\OneToMany(targetEntity: Discussion::class, mappedBy: 'publisher')]
    private Collection $publisherDiscussions;

    /**
     * @var Collection<int, Discussion>
     * Discussions où l'utilisateur est l'artiste
     */
    #[ORM\OneToMany(targetEntity: Discussion::class, mappedBy: 'artist')]
    private Collection $artistDiscussions;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'sender')]
    private Collection $messages;

    /**
     * @var Collection<int, Contract>
     * Contrats signés par cet utilisateur
     */
    #[ORM\OneToMany(targetEntity: Contract::class, mappedBy: 'signedBy')]
    private Collection $signedContracts;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->publisherDiscussions = new ArrayCollection();
        $this->artistDiscussions = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->signedContracts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Un identifiant visuel qui représente cet utilisateur.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // Garantir que chaque utilisateur a au moins ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // Si vous stockez des données sensibles temporaires, nettoyez-les ici
        // $this->plainPassword = null;
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

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName) ?: $this->username;
    }

    public function setFullName(string $fullName): static
    {
        $parts = explode(' ', $fullName, 2);
        $this->firstName = $parts[0] ?? '';
        $this->lastName = $parts[1] ?? '';
        
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setArtist($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getArtist() === $this) {
                $product->setArtist(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Discussion>
     */
    public function getPublisherDiscussions(): Collection
    {
        return $this->publisherDiscussions;
    }

    public function addPublisherDiscussion(Discussion $discussion): static
    {
        if (!$this->publisherDiscussions->contains($discussion)) {
            $this->publisherDiscussions->add($discussion);
            $discussion->setPublisher($this);
        }

        return $this;
    }

    public function removePublisherDiscussion(Discussion $discussion): static
    {
        if ($this->publisherDiscussions->removeElement($discussion)) {
            if ($discussion->getPublisher() === $this) {
                $discussion->setPublisher(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Discussion>
     */
    public function getArtistDiscussions(): Collection
    {
        return $this->artistDiscussions;
    }

    public function addArtistDiscussion(Discussion $discussion): static
    {
        if (!$this->artistDiscussions->contains($discussion)) {
            $this->artistDiscussions->add($discussion);
            $discussion->setArtist($this);
        }

        return $this;
    }

    public function removeArtistDiscussion(Discussion $discussion): static
    {
        if ($this->artistDiscussions->removeElement($discussion)) {
            if ($discussion->getArtist() === $this) {
                $discussion->setArtist(null);
            }
        }

        return $this;
    }

    /**
     * Récupère toutes les discussions de l'utilisateur (artiste ou publisher)
     * 
     * @return Collection<int, Discussion>
     */
    public function getAllDiscussions(): Collection
    {
        $discussions = new ArrayCollection();
        
        foreach ($this->artistDiscussions as $discussion) {
            $discussions->add($discussion);
        }
        
        foreach ($this->publisherDiscussions as $discussion) {
            $discussions->add($discussion);
        }
        
        return $discussions;
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
            $message->setSender($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getSender() === $this) {
                $message->setSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Contract>
     */
    public function getSignedContracts(): Collection
    {
        return $this->signedContracts;
    }

    public function addSignedContract(Contract $signedContract): static
    {
        if (!$this->signedContracts->contains($signedContract)) {
            $this->signedContracts->add($signedContract);
            $signedContract->setSignedBy($this);
        }

        return $this;
    }

    public function removeSignedContract(Contract $signedContract): static
    {
        if ($this->signedContracts->removeElement($signedContract)) {
            if ($signedContract->getSignedBy() === $this) {
                $signedContract->setSignedBy(null);
            }
        }

        return $this;
    }

    /**
     * Vérifie si l'utilisateur est un artiste
     */
    public function isArtist(): bool
    {
        return $this->type === 'artist';
    }

    /**
     * Vérifie si l'utilisateur est un publisher
     */
    public function isPublisher(): bool
    {
        return $this->type === 'publisher';
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}
