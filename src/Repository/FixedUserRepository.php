<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class FixedUserRepository extends EntityRepository
{
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        // Prevent empty WHERE clause
        if (empty($criteria)) {
            // Return null or throw exception based on your needs
            return null;
            // Or to get the first user: return $this->createQueryBuilder('u')->setMaxResults(1)->getQuery()->getOneOrNullResult();
        }
        
        return parent::findOneBy($criteria, $orderBy);
    }
    
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        // Prevent empty WHERE clause
        if (empty($criteria)) {
            // Return empty array or all users with limit
            if ($limit) {
                return $this->createQueryBuilder('u')
                    ->setMaxResults($limit)
                    ->setFirstResult($offset ?? 0)
                    ->getQuery()
                    ->getResult();
            }
            return [];
        }
        
        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }
}