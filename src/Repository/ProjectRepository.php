<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * Find products by category
     */
    public function findByCategorie(string $categorieLabel): array
    {
        return $this->createQueryBuilder('p')
              ->where('p.categoryLabel = :category')
              ->setParameter('category', $categoryLabel)
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find products by price range
     */
    public function findByPriceRange(float $minPrice, float $maxPrice): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.prix >= :minPrice AND p.prix <= :maxPrice')
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->orderBy('p.prix', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all categories
     */
    public function findAllCategories(): array
    {
        $result = $this->createQueryBuilder('p')
              ->select('DISTINCT p.categoryLabel AS label')
              ->where('p.categoryLabel IS NOT NULL')
              ->orderBy('p.categoryLabel', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($result, 'label');
    }

    /**
     * Search products by name or description
     */
    public function search(string $keyword): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.nom LIKE :keyword OR p.description LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
