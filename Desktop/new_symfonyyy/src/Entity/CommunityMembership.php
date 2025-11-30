<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\CommunityMembershipRepository::class)]
#[ORM\Table(name: 'community_membership')]
class CommunityMembership
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Community::class, inversedBy: 'memberships')]
    private ?Community $community = null;

    #[ORM\Column(type:'integer')]
    private int $userId;

    #[ORM\Column(type:'string', length:32)]
    private string $role = 'member';

    #[ORM\Column(type:'string', length:20)]
    private string $status = 'active';

    #[ORM\Column(type:'datetime')]
    private \DateTimeInterface $joinedAt;

    public function __construct()
    {
        $this->joinedAt = new \DateTime();
    }
}
