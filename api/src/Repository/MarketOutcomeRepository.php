<?php

namespace App\Repository;

use App\Entity\GameMarket;
use App\Entity\MarketOutcome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MarketOutcome>
 */
class MarketOutcomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarketOutcome::class);
    }

    /** @return MarketOutcome[] */
    public function findByMarket(GameMarket $market): array
    {
        return $this->findBy(['market' => $market]);
    }

    public function findOneByMarketAndName(GameMarket $market, string $name): ?MarketOutcome
    {
        return $this->findOneBy(['market' => $market, 'name' => $name]);
    }
}
