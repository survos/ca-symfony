<?php

namespace App\Repository;

use App\Entity\PhpFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PhpFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhpFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhpFile[]    findAll()
 * @method PhpFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhpFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhpFile::class);
    }

    // /**
    //  * @return PhpFile[] Returns an array of PhpFile objects
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
    public function findOneBySomeField($value): ?PhpFile
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
