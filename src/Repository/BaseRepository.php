<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class BaseRepository extends EntityRepository
{
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        // Fix for empty WHERE clause
        if (empty($criteria)) {
            // Log the issue for debugging
            // error_log('Empty findOneBy() called in ' . get_class($this));
            return null;
        }
        
        return parent::findOneBy($criteria, $orderBy);
    }
    
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        // Fix for empty WHERE clause
        if (empty($criteria)) {
            // Log the issue for debugging
            // error_log('Empty findBy() called in ' . get_class($this));
            
            if ($limit) {
                // Return limited results without WHERE
                $qb = $this->createQueryBuilder('e');
                if ($orderBy) {
                    foreach ($orderBy as $field => $direction) {
                        $qb->addOrderBy('e.' . $field, $direction);
                    }
                }
                return $qb->setMaxResults($limit)
                    ->setFirstResult($offset ?? 0)
                    ->getQuery()
                    ->getResult();
            }
            return [];
        }
        
        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }
}