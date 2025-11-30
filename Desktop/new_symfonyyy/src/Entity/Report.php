<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\ReportRepository::class)]
#[ORM\Table(name: 'report')]
class Report
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\Column(type:'integer', nullable:true)]
    private ?int $reporterId = null;

    #[ORM\Column(type:'string', length:64)]
    private string $targetType;

    #[ORM\Column(type:'integer')]
    private int $targetId;

    #[ORM\Column(type:'string', length:120)]
    private string $reason;

    #[ORM\Column(type:'text', nullable:true)]
    private ?string $details = null;

    #[ORM\Column(type:'string', length:20)]
    private string $status = 'open';

    #[ORM\Column(type:'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
}
