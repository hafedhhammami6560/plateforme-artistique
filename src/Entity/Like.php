<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\LikeRepository::class)]
#[ORM\Table(name: '"like"')]
class Like
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\Column(type:'integer')]
    private int $userId;

    #[ORM\Column(type:'string', length:64)]
    private string $targetType;

    #[ORM\Column(type:'integer')]
    private int $targetId;

    #[ORM\Column(type:'smallint')]
    private int $value = 1; // 1 like, -1 dislike

    #[ORM\Column(type:'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }
}
