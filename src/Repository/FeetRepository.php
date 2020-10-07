<?php

namespace App\Repository;

use App\Entity\Feet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Feet|null find($id, $lockMode = null, $lockVersion = null)
 * @method Feet|null findOneBy(array $criteria, array $orderBy = null)
 * @method Feet[]    findAll()
 * @method Feet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feet::class);
    }

    // /**
    //  * @return Feet[] Returns an array of Feet objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Feet
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
