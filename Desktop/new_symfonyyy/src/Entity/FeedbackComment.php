<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\FeedbackCommentRepository::class)]
#[ORM\Table(name: 'feedback_comment')]
class FeedbackComment
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Feedback::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Feedback $feedback = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $authorId = null;

    #[ORM\Column(type: 'string', length:180, nullable: true)]
    private ?string $authorName = null;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?self $parent = null;

    #[ORM\Column(type:'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getFeedback(): ?Feedback { return $this->feedback; }
    public function setFeedback(?Feedback $f): self { $this->feedback = $f; return $this; }
    public function getAuthorId(): ?int { return $this->authorId; }
    public function setAuthorId(?int $i): self { $this->authorId = $i; return $this; }
    public function getAuthorName(): ?string { return $this->authorName; }
    public function setAuthorName(?string $n): self { $this->authorName = $n; return $this; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $c): self { $this->content = $c; return $this; }
    public function getParent(): ?self { return $this->parent; }
    public function setParent(?self $p): self { $this->parent = $p; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
