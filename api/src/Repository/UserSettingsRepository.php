<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSettings::class);
    }

    public function findByUser(User $user): ?UserSettings
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function provisionForUser(User $user): UserSettings
    {
        $settings = $this->findByUser($user);
        if ($settings) {
            return $settings;
        }

        $settings = new UserSettings($user);
        $this->getEntityManager()->persist($settings);
        $this->getEntityManager()->flush();

        return $settings;
    }
}
