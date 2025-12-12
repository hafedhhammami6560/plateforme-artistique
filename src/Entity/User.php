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
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

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

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $userType = null;

    #[ORM\OneToMany(targetEntity: Contrat::class, mappedBy: 'producteur')]
    private Collection $contratsAsProducteur;

    #[ORM\OneToMany(targetEntity: Contrat::class, mappedBy: 'artiste')]
    private Collection $contratsAsArtiste;

    #[ORM\OneToMany(targetEntity: Discussion::class, mappedBy: 'initiateur')]
    private Collection $discussionsInitiees;

    #[ORM\OneToMany(targetEntity: Discussion::class, mappedBy: 'destinataire')]
    private Collection $discussionsRecues;

    public function __construct()
    {
        $this->contratsAsProducteur = new ArrayCollection();
        $this->contratsAsArtiste = new ArrayCollection();
        $this->discussionsInitiees = new ArrayCollection();
        $this->discussionsRecues = new ArrayCollection();
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

    /**
     * @return Collection<int, Contrat>
     */
    public function getContratsAsProducteur(): Collection
    {
        return $this->contratsAsProducteur;
    }

    public function addContratAsProducteur(Contrat $contrat): static
    {
        if (!$this->contratsAsProducteur->contains($contrat)) {
            $this->contratsAsProducteur->add($contrat);
            $contrat->setProducteur($this);
        }

        return $this;
    }

    public function removeContratAsProducteur(Contrat $contrat): static
    {
        if ($this->contratsAsProducteur->removeElement($contrat)) {
            if ($contrat->getProducteur() === $this) {
                $contrat->setProducteur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Contrat>
     */
    public function getContratsAsArtiste(): Collection
    {
        return $this->contratsAsArtiste;
    }

    public function addContratAsArtiste(Contrat $contrat): static
    {
        if (!$this->contratsAsArtiste->contains($contrat)) {
            $this->contratsAsArtiste->add($contrat);
            $contrat->setArtiste($this);
        }

        return $this;
    }

    public function removeContratAsArtiste(Contrat $contrat): static
    {
        if ($this->contratsAsArtiste->removeElement($contrat)) {
            if ($contrat->getArtiste() === $this) {
                $contrat->setArtiste(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Discussion>
     */
    public function getDiscussionsInitiees(): Collection
    {
        return $this->discussionsInitiees;
    }

    public function addDiscussionInitiee(Discussion $discussion): static
    {
        if (!$this->discussionsInitiees->contains($discussion)) {
            $this->discussionsInitiees->add($discussion);
            $discussion->setInitiateur($this);
        }

        return $this;
    }

    public function removeDiscussionInitiee(Discussion $discussion): static
    {
        if ($this->discussionsInitiees->removeElement($discussion)) {
            if ($discussion->getInitiateur() === $this) {
                $discussion->setInitiateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Discussion>
     */
    public function getDiscussionsRecues(): Collection
    {
        return $this->discussionsRecues;
    }

    public function addDiscussionRecue(Discussion $discussion): static
    {
        if (!$this->discussionsRecues->contains($discussion)) {
            $this->discussionsRecues->add($discussion);
            $discussion->setDestinataire($this);
        }

        return $this;
    }

    public function removeDiscussionRecue(Discussion $discussion): static
    {
        if ($this->discussionsRecues->removeElement($discussion)) {
            if ($discussion->getDestinataire() === $this) {
                $discussion->setDestinataire(null);
            }
        }

        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeInterface $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;
        return $this;
    }

    public function getUserType(): ?string
    {
        return $this->userType;
    }

    public function setUserType(?string $userType): static
    {
        $this->userType = $userType;
        return $this;
    }
}
