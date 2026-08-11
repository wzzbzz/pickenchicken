<?php

namespace App\Repository;

use App\Entity\PlayerProgress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerProgress>
 */
class PlayerProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerProgress::class);
    }

    public function findByUserAndCompetition(int $userId, int $competitionId): ?PlayerProgress
    {
        return $this->createQueryBuilder('pp')
            ->where('pp.user = :userId')
            ->andWhere('pp.competition = :competitionId')
            ->setParameter('userId', $userId)
            ->setParameter('competitionId', $competitionId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
