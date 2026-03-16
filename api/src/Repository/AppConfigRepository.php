<?php

namespace App\Repository;

use App\Entity\AppConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppConfig>
 */
class AppConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppConfig::class);
    }

    public function get(string $key): ?string
    {
        $config = $this->find($key);
        return $config?->getValue();
    }

    public function set(string $key, ?string $value): void
    {
        $config = $this->find($key) ?? new AppConfig($key);
        $config->setValue($value);
        $this->getEntityManager()->persist($config);
        $this->getEntityManager()->flush();
    }
}
