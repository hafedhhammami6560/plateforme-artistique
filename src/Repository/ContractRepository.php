<?php

namespace App\Repository;

use App\Entity\Contract;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contract>
 */
class ContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contract::class);
    }

    /**
     * Récupère les contrats par statut
     * 
     * @return Contract[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.discussion', 'd')
            ->addSelect('d')
            ->leftJoin('d.artist', 'a')
            ->addSelect('a')
            ->leftJoin('d.publisher', 'p')
            ->addSelect('p')
            ->leftJoin('d.product', 'prod')
            ->addSelect('prod')
            ->where('c.status = :status')
            ->setParameter('status', $status)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les contrats expirant bientôt
     * 
     * @return Contract[]
     */
    public function findExpiringContracts(int $days = 30): array
    {
        $now = new \DateTime();
        $futureDate = new \DateTime('+' . $days . ' days');

        return $this->createQueryBuilder('c')
            ->leftJoin('c.discussion', 'd')
            ->addSelect('d')
            ->leftJoin('d.artist', 'a')
            ->addSelect('a')
            ->leftJoin('d.publisher', 'p')
            ->addSelect('p')
            ->where('c.status IN (:statuses)')
            ->andWhere('c.endDate BETWEEN :now AND :futureDate')
            ->setParameter('statuses', [Contract::STATUS_SIGNED, Contract::STATUS_ACTIVE])
            ->setParameter('now', $now)
            ->setParameter('futureDate', $futureDate)
            ->orderBy('c.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les contrats signés par un utilisateur
     * 
     * @return Contract[]
     */
    public function findSignedByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.discussion', 'd')
            ->addSelect('d')
            ->leftJoin('d.product', 'prod')
            ->addSelect('prod')
            ->where('c.signedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('c.signedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les contrats d'un utilisateur (artiste ou publisher)
     * 
     * @return Contract[]
     */
    public function findForUser(User $user, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.discussion', 'd')
            ->addSelect('d')
            ->leftJoin('d.artist', 'a')
            ->addSelect('a')
            ->leftJoin('d.publisher', 'p')
            ->addSelect('p')
            ->leftJoin('d.product', 'prod')
            ->addSelect('prod')
            ->where('d.artist = :user OR d.publisher = :user')
            ->setParameter('user', $user);

        if ($status) {
            $qb->andWhere('c.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques de commission
     * Retourne les taux de commission et le nombre de contrats pour chaque taux
     */
    public function getCommissionStats(): array
    {
        $result = $this->createQueryBuilder('c')
            ->select('c.commissionRate, COUNT(c.id) as count')
            ->where('c.status IN (:statuses)')
            ->setParameter('statuses', [Contract::STATUS_SIGNED, Contract::STATUS_ACTIVE])
            ->groupBy('c.commissionRate')
            ->orderBy('c.commissionRate', 'ASC')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($result as $row) {
            $stats[(float) $row['commissionRate']] = (int) $row['count'];
        }

        return $stats;
    }

    /**
     * Récupère les contrats actifs
     * 
     * @return Contract[]
     */
    public function findActiveContracts(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.discussion', 'd')
            ->addSelect('d')
            ->leftJoin('d.artist', 'a')
            ->addSelect('a')
            ->leftJoin('d.publisher', 'p')
            ->addSelect('p')
            ->leftJoin('d.product', 'prod')
            ->addSelect('prod')
            ->where('c.status = :status')
            ->setParameter('status', Contract::STATUS_ACTIVE)
            ->orderBy('c.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les contrats avec une commission supérieure à un certain taux
     * 
     * @return Contract[]
     */
    public function findByMinCommission(float $minRate = 20.0): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.discussion', 'd')
            ->addSelect('d')
            ->leftJoin('d.artist', 'a')
            ->addSelect('a')
            ->leftJoin('d.publisher', 'p')
            ->addSelect('p')
            ->where('c.commissionRate >= :minRate')
            ->setParameter('minRate', $minRate)
            ->orderBy('c.commissionRate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les contrats par statut
     */
    public function countByStatus(): array
    {
        $result = $this->createQueryBuilder('c')
            ->select('c.status, COUNT(c.id) as count')
            ->groupBy('c.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Compte les contrats par statut pour un utilisateur spécifique
     */
    public function countByStatusForUser(User $user): array
    {
        $result = $this->createQueryBuilder('c')
            ->select('c.status, COUNT(c.id) as count')
            ->leftJoin('c.discussion', 'd')
            ->where('d.artist = :user OR d.publisher = :user')
            ->setParameter('user', $user)
            ->groupBy('c.status')
            ->getQuery()
            ->getResult();

        $counts = [
            'draft' => 0,
            'proposed' => 0,
            'signed' => 0,
            'active' => 0,
            'terminated' => 0,
        ];
        
        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Récupère les contrats récents
     * 
     * @return Contract[]
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.discussion', 'd')
            ->addSelect('d')
            ->leftJoin('d.artist', 'a')
            ->addSelect('a')
            ->leftJoin('d.publisher', 'p')
            ->addSelect('p')
            ->leftJoin('d.product', 'prod')
            ->addSelect('prod')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule la commission moyenne des contrats actifs
     */
    public function getAverageCommission(): float
    {
        $result = $this->createQueryBuilder('c')
            ->select('AVG(c.commissionRate) as avgCommission')
            ->where('c.status IN (:statuses)')
            ->setParameter('statuses', [Contract::STATUS_SIGNED, Contract::STATUS_ACTIVE])
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Récupère les contrats expirés qui ne sont pas terminés
     * 
     * @return Contract[]
     */
    public function findExpiredNotTerminated(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('c')
            ->leftJoin('c.discussion', 'd')
            ->addSelect('d')
            ->where('c.endDate < :now')
            ->andWhere('c.status != :terminated')
            ->setParameter('now', $now)
            ->setParameter('terminated', Contract::STATUS_TERMINATED)
            ->orderBy('c.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de contrats par référence ou contenu
     * 
     * @return Contract[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.discussion', 'd')
            ->addSelect('d')
            ->leftJoin('d.artist', 'a')
            ->addSelect('a')
            ->leftJoin('d.publisher', 'p')
            ->addSelect('p')
            ->leftJoin('d.product', 'prod')
            ->addSelect('prod')
            ->where('c.referenceNumber LIKE :query')
            ->orWhere('c.terms LIKE :query')
            ->orWhere('a.username LIKE :query')
            ->orWhere('p.username LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les statistiques globales des contrats
     */
    public function getGlobalStats(): array
    {
        $totalContracts = $this->count([]);
        $signedContracts = $this->count(['status' => Contract::STATUS_SIGNED]);
        $activeContracts = $this->count(['status' => Contract::STATUS_ACTIVE]);
        $averageCommission = $this->getAverageCommission();

        return [
            'total' => $totalContracts,
            'signed' => $signedContracts,
            'active' => $activeContracts,
            'averageCommission' => round($averageCommission, 2),
        ];
    }
}
