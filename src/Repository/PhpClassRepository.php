<?php

namespace App\Repository;

use App\Entity\PhpClass;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PhpClass|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhpClass|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhpClass[]    findAll()
 * @method PhpClass[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhpClassRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhpClass::class);
    }

    // /**
    //  * @return PhpClass[] Returns an array of PhpClass objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PhpClass
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
