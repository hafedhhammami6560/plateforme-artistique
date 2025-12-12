<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\FollowRepository::class)]
#[ORM\Table(name: 'follow')]
class Follow
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\Column(type:'integer')]
    private int $followerId;

    #[ORM\Column(type:'integer')]
    private int $followingId;

    #[ORM\Column(type:'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }
}
