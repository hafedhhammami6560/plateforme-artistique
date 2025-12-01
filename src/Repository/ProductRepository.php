<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Récupère les produits publiés
     * 
     * @return Product[]
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les produits d'un artiste
     * 
     * @return Product[]
     */
    public function findByArtist(User $artist): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.artist = :artist')
            ->setParameter('artist', $artist)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les produits par catégorie
     * 
     * @return Product[]
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.category = :category')
            ->andWhere('p.status = :status')
            ->setParameter('category', $category)
            ->setParameter('status', 'published')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de produits par titre ou description
     * 
     * @return Product[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.title LIKE :query OR p.description LIKE :query')
            ->andWhere('p.status = :status')
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('status', 'published')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
