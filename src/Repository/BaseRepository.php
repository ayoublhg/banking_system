<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class BaseRepository extends EntityRepository
{
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        
        if (empty($criteria)) {
            
            return null;
        }
        
        return parent::findOneBy($criteria, $orderBy);
    }
    
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        
        if (empty($criteria)) {
            
            
            if ($limit) {
                
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