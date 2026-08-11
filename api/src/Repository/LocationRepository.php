<?php

namespace App\Repository;

use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Location>
 */
class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    public function findByZone(string $zone): ?Location
    {
        return $this->findOneBy(['zone' => $zone]);
    }

    public function findByLadderPosition(int $position): ?Location
    {
        return $this->createQueryBuilder('l')
            ->where('l.minLadderPosition <= :pos')
            ->andWhere('l.maxLadderPosition >= :pos')
            ->setParameter('pos', $position)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
