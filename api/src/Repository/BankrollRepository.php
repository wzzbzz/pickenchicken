<?php

namespace App\Repository;

use App\Entity\Bankroll;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bankroll>
 */
class BankrollRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bankroll::class);
    }

    public function findOneForUser(int $userId, bool $isFae): ?Bankroll
    {
        return $this->createQueryBuilder('b')
            ->where('b.user = :userId')
            ->andWhere('b.isFae = :isFae')
            ->setParameter('userId', $userId)
            ->setParameter('isFae', $isFae)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
