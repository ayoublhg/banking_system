<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findRecentTransactions(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByClientAndType($client, string $type): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.client = :client')
            ->andWhere('t.type = :type')
            ->setParameter('client', $client)
            ->setParameter('type', $type)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}