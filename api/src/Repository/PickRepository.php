<?php

namespace App\Repository;

use App\Entity\MarketOutcome;
use App\Entity\Pick;
use App\Entity\TournamentGame;
use App\Entity\TournamentRound;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pick>
 */
class PickRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pick::class);
    }

    public function findOneByUserAndGame(User $user, TournamentGame $game): ?Pick
    {
        return $this->findOneBy(['user' => $user, 'game' => $game]);
    }

    /** @return Pick[] */
    public function findByUserAndRound(User $user, TournamentRound $round): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.game', 'g')
            ->where('p.user = :user')
            ->andWhere('g.round = :round')
            ->setParameter('user', $user)
            ->setParameter('round', $round)
            ->getQuery()
            ->getResult();
    }

    /** @return Pick[] */
    public function findByGame(TournamentGame $game): array
    {
        return $this->findBy(['game' => $game]);
    }
}
