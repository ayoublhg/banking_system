<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Service>
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    public function findActiveServices(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFeaturedServices(int $limit = 6): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByPriceRange(float $minPrice, float $maxPrice): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.isActive = :active')
            ->andWhere('s.price BETWEEN :minPrice AND :maxPrice')
            ->setParameter('active', true)
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->orderBy('s.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchServices(string $searchTerm): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.isActive = :active')
            ->andWhere('s.name LIKE :searchTerm OR s.description LIKE :searchTerm')
            ->setParameter('active', true)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}