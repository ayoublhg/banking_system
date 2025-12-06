<?php

namespace App\Doctrine;

use Doctrine\ORM\EntityRepository as BaseEntityRepository;

class PatchedEntityRepository extends BaseEntityRepository
{
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        // EMERGENCY FIX: Prevent SQL error from empty WHERE clause
        if (empty($criteria)) {
            // Instead of returning null, let's get the first record
            // This might not be what you want, but it prevents the SQL error
            $result = $this->createQueryBuilder('e')
                ->setMaxResults(1);
                
            if ($orderBy) {
                foreach ($orderBy as $field => $direction) {
                    $result->addOrderBy('e.' . $field, $direction);
                }
            }
            
            return $result->getQuery()->getOneOrNullResult();
        }
        
        return parent::findOneBy($criteria, $orderBy);
    }
    
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        // EMERGENCY FIX: Prevent SQL error from empty WHERE clause
        if (empty($criteria)) {
            $qb = $this->createQueryBuilder('e');
            
            if ($orderBy) {
                foreach ($orderBy as $field => $direction) {
                    $qb->addOrderBy('e.' . $field, $direction);
                }
            }
            
            if ($limit) {
                $qb->setMaxResults($limit);
            }
            
            if ($offset) {
                $qb->setFirstResult($offset);
            }
            
            return $qb->getQuery()->getResult();
        }
        
        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }
}