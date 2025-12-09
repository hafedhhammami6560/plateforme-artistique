<?php

namespace App\Repository;

use App\Entity\Signature;
use App\Entity\Contrat;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Signature>
 */
class SignatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Signature::class);
    }

    /**
     * Récupère toutes les signatures d'un contrat
     */
    public function findByContrat(Contrat $contrat): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.contrat = :contrat')
            ->setParameter('contrat', $contrat)
            ->orderBy('s.signedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère la signature d'un utilisateur pour un contrat
     */
    public function findByContratAndUser(Contrat $contrat, User $user): ?Signature
    {
        return $this->createQueryBuilder('s')
            ->where('s.contrat = :contrat')
            ->andWhere('s.signataire = :user')
            ->setParameter('contrat', $contrat)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère une signature par son token
     */
    public function findByToken(string $token): ?Signature
    {
        return $this->createQueryBuilder('s')
            ->where('s.signatureToken = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte les signatures valides d'un contrat
     */
    public function countValidSignatures(Contrat $contrat): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.contrat = :contrat')
            ->andWhere('s.status = :status')
            ->setParameter('contrat', $contrat)
            ->setParameter('status', Signature::STATUS_VALID)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les signatures expirées
     */
    public function findExpiredSignatures(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.expiresAt <= :now')
            ->andWhere('s.status = :status')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('status', Signature::STATUS_VALID)
            ->getQuery()
            ->getResult();
    }
}
