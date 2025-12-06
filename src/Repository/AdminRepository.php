<?php

namespace App\Repository;

use App\Entity\Admin;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends UserRepository<Admin>
 */
class AdminRepository extends UserRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Admin::class);
    }

    // Add your custom methods here
    public function findByDepartment(string $department): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.department = :department')
            ->setParameter('department', $department)
            ->orderBy('a.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveAdmins(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.isVerified = :verified')
            ->setParameter('verified', true)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}