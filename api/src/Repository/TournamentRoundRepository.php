<?php

namespace App\Repository;

use App\Entity\TournamentRound;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentRound>
 */
class TournamentRoundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentRound::class);
    }

    public function findCurrentRound(): ?TournamentRound
    {
        return $this->findOneBy(['status' => 'in_progress']);
    }

    public function findByRoundNumber(int $roundNumber): ?TournamentRound
    {
        return $this->findOneBy(['roundNumber' => $roundNumber]);
    }
}
