<?php

namespace App\Repository;

use App\Entity\Contrat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contrat>
 */
class ContratRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contrat::class);
    }

    /**
     * Find contracts by status
     */
    public function findByStatut(string $statut): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active contracts
     */
    public function findActive(): array
    {
        return $this->findByStatut(Contrat::STATUT_ACTIF);
    }

    /**
     * Find pending contracts
     */
    public function findPending(): array
    {
        return $this->findByStatut(Contrat::STATUT_EN_ATTENTE);
    }

    /**
     * Find contracts by producer
     */
    public function findByProducer($producer): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.producteur = :producer')
            ->setParameter('producer', $producer)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find contracts by artist
     */
    public function findByArtist($artist): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.artiste = :artist')
            ->setParameter('artist', $artist)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find contracts by producer and artist
     */
    public function findByProducerAndArtist($producer, $artist): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.producteur = :producer AND c.artiste = :artist')
            ->setParameter('producer', $producer)
            ->setParameter('artist', $artist)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculate total amount of active contracts
     */
    public function getTotalActiveAmount(): float
    {
        $result = $this->createQueryBuilder('c')
            ->select('SUM(c.montant) as total')
            ->where('c.statut = :statut')
            ->setParameter('statut', Contrat::STATUT_ACTIF)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Count contracts by status
     */
    public function countByStatut(string $statut): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.statut = :statut')
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
