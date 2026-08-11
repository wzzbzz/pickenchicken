<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * Games for a sport across a date range, independent of already knowing
     * a segment id — the feed a client browses picks from.
     *
     * @return Game[]
     */
    public function findFeed(string $sportKey, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.segment', 's')
            ->join('s.competition', 'c')
            ->where('c.sportKey = :sportKey')
            ->andWhere('g.commenceTime BETWEEN :start AND :end')
            ->setParameter('sportKey', $sportKey)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('g.commenceTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
