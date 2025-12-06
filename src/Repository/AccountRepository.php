<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    // AJOUTER cette méthode
    public function findAllWithClients(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.client', 'c')
            ->addSelect('c')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByAccountNumber(string $accountNumber): ?Account
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.accountNumber = :accountNumber')
            ->setParameter('accountNumber', $accountNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveAccountsByClient(Client $client): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.client = :client')
            ->andWhere('a.isActive = true')
            ->setParameter('client', $client)
            ->orderBy('a.type', 'ASC')
            ->addOrderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalBalanceByClient(Client $client): float
    {
        $result = $this->createQueryBuilder('a')
            ->select('COALESCE(SUM(a.balance), 0)')
            ->andWhere('a.client = :client')
            ->andWhere('a.isActive = true')
            ->setParameter('client', $client)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.type = :type')
            ->andWhere('a.isActive = true')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }

    // AJOUTER: Recherche de comptes
    public function search(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.client', 'c')
            ->where('a.accountNumber LIKE :query')
            ->orWhere('c.firstName LIKE :query')
            ->orWhere('c.lastName LIKE :query')
            ->orWhere('c.email LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}