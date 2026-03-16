<?php

namespace App\Repository;

use App\Entity\Score;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ScoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Score::class);
    }

    /**
     * Get all-time leaderboard
     */
    public function getAllTimeLeaderboard(int $limit = 100): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.score', 'u.username', 'u.email', 's.createdAt')
            ->join('s.user', 'u')
            ->orderBy('s.score', 'DESC')
            ->addOrderBy('s.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get daily leaderboard (today's scores only)
     */
    public function getDailyLeaderboard(int $limit = 100): array
    {
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');

        return $this->createQueryBuilder('s')
            ->select('s.score', 'u.username', 'u.email', 's.createdAt')
            ->join('s.user', 'u')
            ->where('s.createdAt >= :today')
            ->andWhere('s.createdAt < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('s.score', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get user's best score ever
     */
    public function getUserBestScore(int $userId): ?int
    {
        $result = $this->createQueryBuilder('s')
            ->select('MAX(s.score) as bestScore')
            ->where('s.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();

        return $result['bestScore'] ?? null;
    }

    /**
     * Get user's rank in all-time leaderboard
     */
    public function getUserAllTimeRank(int $userId): ?int
    {
        $bestScore = $this->getUserBestScore($userId);
        
        if ($bestScore === null) {
            return null;
        }

        $result = $this->createQueryBuilder('s')
            ->select('COUNT(DISTINCT s2.score) + 1 as rank')
            ->from(Score::class, 's2')
            ->where('s2.score > :bestScore')
            ->setParameter('bestScore', $bestScore)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }
}
