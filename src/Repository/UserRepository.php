<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
    
    
    public function findOneBy(array $criteria, array $orderBy = null)
    {
     
        if (empty($criteria)) {
            
            return null;
        }
        
        
        $qb = $this->createQueryBuilder('u');
        
        $paramIndex = 0;
        foreach ($criteria as $field => $value) {
            $paramName = 'param_' . $paramIndex++;
            $qb->andWhere("u.{$field} = :{$paramName}")
               ->setParameter($paramName, $value);
        }
        
        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy('u.' . $field, $direction);
            }
        }
        
        $qb->setMaxResults(1);
        
        return $qb->getQuery()->getOneOrNullResult();
    }
    
    
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        
        if (empty($criteria)) {
            
            return [];
        }
        
        
        $qb = $this->createQueryBuilder('u');
        
        $paramIndex = 0;
        foreach ($criteria as $field => $value) {
            $paramName = 'param_' . $paramIndex++;
            $qb->andWhere("u.{$field} = :{$paramName}")
               ->setParameter($paramName, $value);
        }
        
        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy('u.' . $field, $direction);
            }
        }
        
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }
        
        return $qb->getQuery()->getResult();
    }
    /**
    * Récupère seulement les utilisateurs de type Client (pas les Admin)
    */
    public function findAllClients(): array
    {
       return $this->createQueryBuilder('u')
        ->where('u INSTANCE OF App\Entity\Client')
        ->orderBy('u.createdAt', 'DESC')
        ->getQuery()
        ->getResult();
}
}