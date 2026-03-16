<?php

namespace App\Repository;

use App\Entity\GameMarket;
use App\Entity\TournamentGame;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameMarket>
 */
class GameMarketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameMarket::class);
    }

    public function findOneByGameAndMarket(TournamentGame $game, string $marketKey, string $bookmaker): ?GameMarket
    {
        return $this->findOneBy(['game' => $game, 'marketKey' => $marketKey, 'bookmaker' => $bookmaker]);
    }

    /** @return GameMarket[] */
    public function findByGame(TournamentGame $game): array
    {
        return $this->findBy(['game' => $game]);
    }

    /** @return GameMarket[] — unlocked markets for scheduled games, ready to be locked */
    public function findUnlocked(string $marketKey = 'spreads'): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.game', 'g')
            ->where('m.marketKey = :key')
            ->andWhere('m.lockedAt IS NULL')
            ->andWhere('g.status = :status')
            ->setParameter('key', $marketKey)
            ->setParameter('status', 'scheduled')
            ->getQuery()
            ->getResult();
    }
}
