<?php

namespace App\Repository;

use App\Entity\UserPick;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserPick>
 */
class UserPickRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPick::class);
    }

    /**
     * A user's full pick history across every competition/segment, newest
     * first — the journal view, as opposed to the per-segment listing
     * GameController::gamesForSegment() provides.
     *
     * @return UserPick[]
     */
    public function findByUserOrdered(int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
