<?php
namespace App\Repository;

use App\Entity\Communite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Communite|null find($id, $lockMode = null, $lockVersion = null)
 * @method Communite|null findOneBy(array $criteria, array $orderBy = null)
 * @method Communite[]    findAll()
 * @method Communite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommuniteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Communite::class);
    }
}
