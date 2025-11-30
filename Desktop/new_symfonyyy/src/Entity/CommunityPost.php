<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: \App\Repository\CommunityPostRepository::class)]
#[ORM\Table(name: 'community_post')]
class CommunityPost
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Community::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable:false, onDelete:'CASCADE')]
    private ?Community $community = null;

    #[ORM\Column(type:'integer', nullable:true)]
    private ?int $authorId = null;

    #[ORM\Column(type:'string', length:32)]
    private string $type = 'text';

    #[ORM\Column(type:'text', nullable:true)]
    private ?string $content = null;

    #[ORM\Column(type:'string', length:255, nullable:true)]
    private ?string $mediaUrl = null;

    #[ORM\Column(type:'integer', nullable:true)]
    private ?int $productId = null;

    #[ORM\Column(type:'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: PostComment::class, cascade:['persist','remove'])]
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
}
