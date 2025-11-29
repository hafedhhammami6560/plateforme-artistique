<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\PostCommentRepository::class)]
#[ORM\Table(name: 'post_comment')]
class PostComment
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CommunityPost::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable:false, onDelete:'CASCADE')]
    private ?CommunityPost $post = null;

    #[ORM\Column(type:'integer', nullable:true)]
    private ?int $authorId = null;

    #[ORM\Column(type:'text')]
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
}
