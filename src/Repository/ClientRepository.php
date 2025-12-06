<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends UserRepository<Client>
 */
class ClientRepository extends UserRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }
    public function findAll(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    public function findByEmail(string $email): ?Client
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveClients()
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isVerified = :verified')
            ->setParameter('verified', true)
            ->getQuery()
            ->getResult();
    }
}