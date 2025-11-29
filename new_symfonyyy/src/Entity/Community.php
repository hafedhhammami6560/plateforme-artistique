<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: \App\Repository\CommunityRepository::class)]
#[ORM\Table(name: 'community')]
class Community
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\Column(type:'string', length:180, unique:true)]
    private string $name;

    #[ORM\Column(type:'string', length:180, unique:true)]
    private string $slug;

    #[ORM\Column(type:'text', nullable:true)]
    private ?string $description = null;

    #[ORM\Column(type:'string', length:32)]
    private string $type = 'general'; // 'artist'|'category'|'general'

    #[ORM\Column(type:'integer', nullable:true)]
    private ?int $ownerId = null;

    #[ORM\Column(type:'boolean')]
    private bool $isPrivate = false;

    #[ORM\OneToMany(mappedBy: 'community', targetEntity: CommunityPost::class, cascade:['persist','remove'])]
    private Collection $posts;

    #[ORM\OneToMany(mappedBy: 'community', targetEntity: CommunityMembership::class, cascade:['persist','remove'])]
    private Collection $memberships;

    #[ORM\Column(type:'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->memberships = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    
    public function getName(): string { return $this->name; }
    public function setName(string $n): self { $this->name = $n; return $this; }
    
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $s): self { $this->slug = $s; return $this; }
    
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): self { $this->description = $d; return $this; }
    
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
