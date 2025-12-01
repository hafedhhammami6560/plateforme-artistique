<?php

namespace App\Repository;

use App\Entity\Discussion;
use App\Entity\Product;
use App\Entity\User;
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
     * Récupère les discussions d'un utilisateur selon son rôle
     * 
     * @return Discussion[]
     */
    public function findForUser(User $user, ?string $status = null, string $orderBy = 'updated'): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.messages', 'm')
            ->addSelect('m')
            ->leftJoin('d.artist', 'a')
            ->addSelect('a')
            ->leftJoin('d.publisher', 'p')
            ->addSelect('p')
            ->leftJoin('d.product', 'prod')
            ->addSelect('prod')
            ->where('d.artist = :user OR d.publisher = :user')
            ->setParameter('user', $user);

        if ($status) {
            $qb->andWhere('d.status = :status')
               ->setParameter('status', $status);
        }

        if ($orderBy === 'recent') {
            $qb->orderBy('d.createdAt', 'DESC');
        } else {
            $qb->orderBy('d.updatedAt', 'DESC')
               ->addOrderBy('d.createdAt', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les discussions actives (en cours de négociation)
     * 
     * @return Discussion[]
     */
    public function findActiveDiscussions(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.artist', 'a')
            ->addSelect('a')
            ->leftJoin('d.publisher', 'p')
            ->addSelect('p')
            ->leftJoin('d.product', 'prod')
            ->addSelect('prod')
            ->where('d.status = :status')
            ->setParameter('status', Discussion::STATUS_ACTIVE)
            ->orderBy('d.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les discussions sans réponse depuis X jours
     * 
     * @return Discussion[]
     */
    public function findStaleDiscussions(int $days = 7): array
    {
        $date = new \DateTimeImmutable('-' . $days . ' days');

        return $this->createQueryBuilder('d')
            ->leftJoin('d.messages', 'm')
            ->addSelect('m')
            ->where('d.status = :status')
            ->andWhere('d.updatedAt < :date OR (d.updatedAt IS NULL AND d.createdAt < :date)')
            ->setParameter('status', Discussion::STATUS_ACTIVE)
            ->setParameter('date', $date)
            ->orderBy('d.updatedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les discussions liées à un produit
     * 
     * @return Discussion[]
     */
    public function findByProduct(Product $product): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.artist', 'a')
            ->addSelect('a')
            ->leftJoin('d.publisher', 'p')
            ->addSelect('p')
            ->where('d.product = :product')
            ->setParameter('product', $product)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les discussions par statut
     * 
     * @return Discussion[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.artist', 'a')
            ->addSelect('a')
            ->leftJoin('d.publisher', 'p')
            ->addSelect('p')
            ->leftJoin('d.product', 'prod')
            ->addSelect('prod')
            ->where('d.status = :status')
            ->setParameter('status', $status)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les discussions avec un contrat
     * 
     * @return Discussion[]
     */
    public function findWithContract(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.contract', 'c')
            ->addSelect('c')
            ->leftJoin('d.artist', 'a')
            ->addSelect('a')
            ->leftJoin('d.publisher', 'p')
            ->addSelect('p')
            ->where('d.contract IS NOT NULL')
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les discussions par statut
     */
    public function countByStatus(): array
    {
        $result = $this->createQueryBuilder('d')
            ->select('d.status, COUNT(d.id) as count')
            ->groupBy('d.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Récupère les discussions récentes (dernières 10)
     * 
     * @return Discussion[]
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.artist', 'a')
            ->addSelect('a')
            ->leftJoin('d.publisher', 'p')
            ->addSelect('p')
            ->leftJoin('d.product', 'prod')
            ->addSelect('prod')
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le taux de conversion des discussions en contrats
     */
    public function getConversionRate(): float
    {
        $totalDiscussions = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->getQuery()
            ->getSingleScalarResult();

        if ($totalDiscussions == 0) {
            return 0.0;
        }

        $discussionsWithContract = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.contract IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return ($discussionsWithContract / $totalDiscussions) * 100;
    }

    /**
     * Recherche des discussions par mot-clé
     * 
     * @return Discussion[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.artist', 'a')
            ->addSelect('a')
            ->leftJoin('d.publisher', 'p')
            ->addSelect('p')
            ->leftJoin('d.product', 'prod')
            ->addSelect('prod')
            ->where('d.subject LIKE :query')
            ->orWhere('prod.title LIKE :query')
            ->orWhere('a.username LIKE :query')
            ->orWhere('p.username LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les discussions entre deux utilisateurs spécifiques
     * 
     * @return Discussion[]
     */
    public function findBetweenUsers(User $user1, User $user2): array
    {
        return $this->createQueryBuilder('d')
            ->where('(d.artist = :user1 AND d.publisher = :user2) OR (d.artist = :user2 AND d.publisher = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
