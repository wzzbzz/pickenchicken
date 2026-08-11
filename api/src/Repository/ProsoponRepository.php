<?php

namespace App\Repository;

use App\Entity\Prosopon;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prosopon>
 */
class ProsoponRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prosopon::class);
    }

    /** All active prosopa at a location, permanent first */
    public function findActiveAtLocation(int $locationId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.location = :loc')
            ->andWhere('p.isActive = true')
            ->setParameter('loc', $locationId)
            ->orderBy('p.isPermanent', 'DESC')
            ->addOrderBy('p.arrivedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPermanentBotAtLocation(int $locationId, string $botKey): ?Prosopon
    {
        return $this->findOneBy([
            'location'    => $locationId,
            'type'        => 'bot',
            'botKey'      => $botKey,
            'isPermanent' => true,
        ]);
    }

    public function findUserProsoponAtLocation(int $locationId, int $userId): ?Prosopon
    {
        return $this->findOneBy([
            'location' => $locationId,
            'type'     => 'user',
            'user'     => $userId,
        ]);
    }
}
