<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: \App\Repository\FeedbackRepository::class)]
#[ORM\Table(name: 'feedback')]
class Feedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // author references (no User entity in project currently)
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $authorId = null;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private ?string $authorName = null;

    #[ORM\Column(type: 'string', length: 32)]
    private string $type = 'product';

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $targetType = null; // 'product'|'artist'|'publisher'

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $targetId = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $rating = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'published';

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\OneToMany(mappedBy: 'feedback', targetEntity: FeedbackComment::class, cascade: ['persist','remove'])]
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getAuthorId(): ?int { return $this->authorId; }
    public function setAuthorId(?int $id): self { $this->authorId = $id; return $this; }
    public function getAuthorName(): ?string { return $this->authorName; }
    public function setAuthorName(?string $n): self { $this->authorName = $n; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $t): self { $this->type = $t; return $this; }
    public function getTargetType(): ?string { return $this->targetType; }
    public function setTargetType(?string $t): self { $this->targetType = $t; return $this; }
    public function getTargetId(): ?int { return $this->targetId; }
    public function setTargetId(?int $id): self { $this->targetId = $id; return $this; }
    public function getRating(): ?int { return $this->rating; }
    public function setRating(?int $r): self { $this->rating = $r; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(?string $c): self { $this->content = $c; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $s): self { $this->status = $s; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
