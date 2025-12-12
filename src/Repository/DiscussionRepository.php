<?php

namespace App\Repository;

use App\Entity\Discussion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Discussion>
 */
class DiscussionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Discussion::class);
    }

    /**
     * Find discussions by status
     */
    public function findByStatut(string $statut): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find open discussions
     */
    public function findOpen(): array
    {
        return $this->findByStatut(Discussion::STATUT_OUVERTE);
    }

    /**
     * Find closed discussions
     */
    public function findClosed(): array
    {
        return $this->findByStatut(Discussion::STATUT_FERMEE);
    }

    /**
     * Find discussions initiated by user
     */
    public function findByInitiateur($initiateur): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.initiateur = :initiateur')
            ->setParameter('initiateur', $initiateur)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find discussions received by user
     */
    public function findByDestinataire($destinataire): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.destinataire = :destinataire')
            ->setParameter('destinataire', $destinataire)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find discussions between two users
     */
    public function findBetweenUsers($user1, $user2): array
    {
        return $this->createQueryBuilder('d')
            ->where('(d.initiateur = :user1 AND d.destinataire = :user2) OR (d.initiateur = :user2 AND d.destinataire = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find discussions linked to a contract
     */
    public function findByContrat($contrat): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.contrat = :contrat')
            ->setParameter('contrat', $contrat)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count discussions by status
     */
    public function countByStatut(string $statut): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.statut = :statut')
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
