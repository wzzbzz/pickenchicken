<?php

namespace App\Repository;

use App\Entity\ChickenPick;
use App\Entity\TournamentGame;
use App\Entity\TournamentRound;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChickenPick>
 */
class ChickenPickRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChickenPick::class);
    }

    public function findOneByGame(TournamentGame $game): ?ChickenPick
    {
        return $this->findOneBy(['game' => $game]);
    }

    /** @return ChickenPick[] indexed by game ID */
    public function findByRoundIndexed(TournamentRound $round): array
    {
        $results = $this->createQueryBuilder('cp')
            ->join('cp.game', 'g')
            ->where('g.round = :round')
            ->setParameter('round', $round)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($results as $cp) {
            $indexed[$cp->getGame()->getId()] = $cp;
        }
        return $indexed;
    }
}
