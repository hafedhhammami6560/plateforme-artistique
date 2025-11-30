<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Entité Community - Représente une communauté d'artistes ou de catégories
 * 
 * Cette classe gère les communautés qui peuvent être de type:
 * - 'general' : Communauté générale ouverte à tous
 * - 'artist' : Communauté dédiée à un artiste spécifique
 * - 'category' : Communauté basée sur une catégorie artistique
 */
#[ORM\Entity(repositoryClass: \App\Repository\CommunityRepository::class)]
#[ORM\Table(name: 'community')]
class Community
{
    /**
     * Identifiant unique auto-incrÃ©mentÃ©
     * @var int|null
     */
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:'integer')]
    private ?int $id = null;

    /**
     * Nom de la communautÃ© (unique)
     * Exemple: "Artistes de Paris", "Photographes Professionnels"
     * @var string
     */
    #[ORM\Column(type:'string', length:180, unique:true)]
    private string $name;

    /**
     * Slug URL-friendly (unique) pour les routes
     * Exemple: "artistes-de-paris", "photographes-professionnels"
     * @var string
     */
    #[ORM\Column(type:'string', length:180, unique:true)]
    private string $slug;

    /**
     * Description dÃ©taillÃ©e de la communautÃ© (optionnelle)
     * @var string|null
     */
    #[ORM\Column(type:'text', nullable:true)]
    private ?string $description = null;

    /**
     * Type de communautÃ©: 'general', 'artist' ou 'category'
     * Valeur par dÃ©faut: 'general'
     * @var string
     */
    #[ORM\Column(type:'string', length:32)]
    private string $type = 'general';

    /**
     * ID du propriÃ©taire de la communautÃ© (optionnel)
     * RÃ©fÃ©rence vers un utilisateur (non implÃ©mentÃ© pour l'instant)
     * @var int|null
     */
    #[ORM\Column(type:'integer', nullable:true)]
    private ?int $ownerId = null;

    /**
     * Indique si la communautÃ© est privÃ©e (true) ou publique (false)
     * Valeur par dÃ©faut: false (publique)
     * @var bool
     */
    #[ORM\Column(type:'boolean')]
    private bool $isPrivate = false;

    /**
     * Collection des posts publiÃ©s dans cette communautÃ©
     * Relation OneToMany: Une communautÃ© peut avoir plusieurs posts
     * Cascade: persist et remove (suppression en cascade)
     * @var Collection<int, CommunityPost>
     */
    #[ORM\OneToMany(mappedBy: 'community', targetEntity: CommunityPost::class, cascade:['persist','remove'])]
    private Collection $posts;

    /**
     * Collection des membres de cette communautÃ©
     * Relation OneToMany: Une communautÃ© peut avoir plusieurs membres
     * Cascade: persist et remove
     * @var Collection<int, CommunityMembership>
     */
    #[ORM\OneToMany(mappedBy: 'community', targetEntity: CommunityMembership::class, cascade:['persist','remove'])]
    private Collection $memberships;

    /**
     * Date de crÃ©ation de la communautÃ©
     * Automatiquement dÃ©finie lors de la construction
     * @var \DateTimeInterface
     */
    #[ORM\Column(type:'datetime')]
    private \DateTimeInterface $createdAt;

    /**
     * Constructeur - Initialise les collections et la date de crÃ©ation
     */
    public function __construct()
    {
        // Initialisation des collections vides pour les relations OneToMany
        $this->posts = new ArrayCollection();
        $this->memberships = new ArrayCollection();
        // DÃ©finir la date de crÃ©ation Ã  maintenant
        $this->createdAt = new \DateTime();
    }

    // ========== GETTERS & SETTERS ==========

    /**
     * RÃ©cupÃ¨re l'ID de la communautÃ©
     * @return int|null
     */
    public function getId(): ?int { return $this->id; }
    
    /**
     * RÃ©cupÃ¨re le nom de la communautÃ©
     * @return string
     */
    public function getName(): string { return $this->name; }
    
    /**
     * DÃ©finit le nom de la communautÃ©
     * @param string $n Le nouveau nom
     * @return self Pour permettre le chaÃ®nage de mÃ©thodes
     */
    public function setName(string $n): self { $this->name = $n; return $this; }
    
    /**
     * RÃ©cupÃ¨re le slug URL de la communautÃ©
     * @return string
     */
    public function getSlug(): string { return $this->slug; }
    
    /**
     * DÃ©finit le slug URL de la communautÃ©
     * @param string $s Le nouveau slug
     * @return self
     */
    public function setSlug(string $s): self { $this->slug = $s; return $this; }
    
    /**
     * RÃ©cupÃ¨re la description de la communautÃ©
     * @return string|null
     */
    public function getDescription(): ?string { return $this->description; }
    
    /**
     * DÃ©finit la description de la communautÃ©
     * @param string|null $d La nouvelle description
     * @return self
     */
    public function setDescription(?string $d): self { $this->description = $d; return $this; }
    
    /**
     * RÃ©cupÃ¨re le type de communautÃ© (general/artist/category)
     * @return string
     */
    public function getType(): string { return $this->type; }
    public function setType(string $t): self { $this->type = $t; return $this; }
    
    public function getOwnerId(): ?int { return $this->ownerId; }
    public function setOwnerId(?int $o): self { $this->ownerId = $o; return $this; }
    
    public function isPrivate(): bool { return $this->isPrivate; }
    public function setIsPrivate(bool $p): self { $this->isPrivate = $p; return $this; }
    
    public function getPosts(): Collection { return $this->posts; }
    public function addPost(CommunityPost $post): self {
        if (!$this->posts->contains($post)) {
            $this->posts[] = $post;
            $post->setCommunity($this);
        }
        return $this;
    }
    public function removePost(CommunityPost $post): self {
        if ($this->posts->removeElement($post)) {
            if ($post->getCommunity() === $this) {
                $post->setCommunity(null);
            }
        }
        return $this;
    }
    
    public function getMemberships(): Collection { return $this->memberships; }
    public function addMembership(CommunityMembership $m): self {
        if (!$this->memberships->contains($m)) {
            $this->memberships[] = $m;
            $m->setCommunity($this);
        }
        return $this;
    }
    public function removeMembership(CommunityMembership $m): self {
        if ($this->memberships->removeElement($m)) {
            if ($m->getCommunity() === $this) {
                $m->setCommunity(null);
            }
        }
        return $this;
    }
    
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $c): self { $this->createdAt = $c; return $this; }
}
