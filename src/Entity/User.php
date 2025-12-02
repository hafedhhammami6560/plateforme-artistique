<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['username'], message: 'This username is already taken')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 100, unique: true, nullable: true)]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 20, nullable: true)]
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
     * @var Collection<int, \App\Entity\Product>
     */
    #[ORM\OneToMany(targetEntity: \App\Entity\Product::class, mappedBy: 'artist', cascade: ['persist', 'remove'])]
    private Collection $products;

    /**
     * @var Collection<int, \App\Entity\Discussion>
     */
    #[ORM\OneToMany(targetEntity: \App\Entity\Discussion::class, mappedBy: 'publisher')]
    private Collection $publisherDiscussions;

    /**
     * @var Collection<int, \App\Entity\Discussion>
     */
    #[ORM\OneToMany(targetEntity: \App\Entity\Discussion::class, mappedBy: 'artist')]
    private Collection $artistDiscussions;

    /**
     * @var Collection<int, \App\Entity\Message>
     */
    #[ORM\OneToMany(targetEntity: \App\Entity\Message::class, mappedBy: 'sender')]
    private Collection $messages;

    /**
     * @var Collection<int, \App\Entity\Contract>
     */
    #[ORM\OneToMany(targetEntity: \App\Entity\Contract::class, mappedBy: 'signedBy')]
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
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

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

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
        if ($this->firstName || $this->lastName) {
            return trim($this->firstName . ' ' . $this->lastName);
        }
        return $this->name ?? $this->username ?? $this->email ?? '';
    }

    public function setFullName(string $fullName): static
    {
        $parts = explode(' ', $fullName, 2);
        $this->firstName = $parts[0] ?? '';
        $this->lastName = $parts[1] ?? '';
        $this->name = $fullName;
        
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

    public function isArtist(): bool
    {
        return $this->type === 'artist';
    }

    public function isPublisher(): bool
    {
        return $this->type === 'publisher';
    }

    /**
     * @return Collection<int, \App\Entity\Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(\App\Entity\Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setArtist($this);
        }

        return $this;
    }

    public function removeProduct(\App\Entity\Product $product): static
    {
        if ($this->products->removeElement($product)) {
            if ($product->getArtist() === $this) {
                $product->setArtist(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, \App\Entity\Discussion>
     */
    public function getPublisherDiscussions(): Collection
    {
        return $this->publisherDiscussions;
    }

    /**
     * @return Collection<int, \App\Entity\Discussion>
     */
    public function getArtistDiscussions(): Collection
    {
        return $this->artistDiscussions;
    }

    /**
     * @return Collection<int, \App\Entity\Discussion>
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
     * @return Collection<int, \App\Entity\Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    /**
     * @return Collection<int, \App\Entity\Contract>
     */
    public function getSignedContracts(): Collection
    {
        return $this->signedContracts;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}
