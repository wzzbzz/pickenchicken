<?php

namespace App\Repository;

use App\Entity\RoundResult;
use App\Entity\TournamentRound;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RoundResult>
 */
class RoundResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoundResult::class);
    }

    public function findOneByUserAndRound(User $user, TournamentRound $round): ?RoundResult
    {
        return $this->findOneBy(['user' => $user, 'round' => $round]);
    }

    /** @return RoundResult[] */
    public function getLeaderboardForRound(TournamentRound $round): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.round = :round')
            ->setParameter('round', $round)
            ->orderBy('r.beatenChickenCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return array Overall leaderboard: user_id, username, total beaten count */
    public function getOverallLeaderboard(): array
    {
        return $this->createQueryBuilder('r')
            ->select('IDENTITY(r.user) as user_id, u.username, u.email, SUM(r.beatenChickenCount) as total')
            ->join('r.user', 'u')
            ->groupBy('r.user, u.username, u.email')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
