<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: \App\Repository\CommunityEventRepository::class)]
#[ORM\Table(name: 'community_event')]
class CommunityEvent
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Community::class)]
    private ?Community $community = null;

    #[ORM\Column(type:'string', length:255)]
    private string $title;

    #[ORM\Column(type:'text', nullable:true)]
    private ?string $description = null;

    #[ORM\Column(type:'datetime')]
    private \DateTimeInterface $startAt;

    #[ORM\Column(type:'datetime', nullable:true)]
    private ?\DateTimeInterface $endAt = null;

    #[ORM\Column(type:'boolean')]
    private bool $isOnline = false;

    // attendees stored as json array of user ids for now
    #[ORM\Column(type:'json', nullable:true)]
    private array $attendees = [];

    #[ORM\Column(type:'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }
}
