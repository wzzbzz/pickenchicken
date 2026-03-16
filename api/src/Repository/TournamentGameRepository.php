<?php

namespace App\Repository;

use App\Entity\TournamentGame;
use App\Entity\TournamentRound;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentGame>
 */
class TournamentGameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentGame::class);
    }

    /** @return TournamentGame[] */
    public function findByRound(TournamentRound $round): array
    {
        return $this->findBy(['round' => $round], ['commenceTime' => 'ASC']);
    }

    public function findByEspnGameId(string $espnGameId): ?TournamentGame
    {
        return $this->findOneBy(['espnGameId' => $espnGameId]);
    }

    /** @return TournamentGame[] */
    public function findFinalisedWithoutResults(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.status = :status')
            ->andWhere('g.winner IS NOT NULL')
            ->setParameter('status', 'final')
            ->getQuery()
            ->getResult();
    }
}
