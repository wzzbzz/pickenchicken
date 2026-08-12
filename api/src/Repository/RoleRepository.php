<?php

namespace App\Repository;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Role>
 */
class RoleRepository extends ServiceEntityRepository
{
    private const DEFAULT_ROLE = 'free';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    public function findOneByName(string $name): ?Role
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * Grants the default ("free") Role to a new user, mirroring
     * WalletRepository::provisionForUser's find-or-create-on-first-use
     * shape. A no-op if the user already has any Role, if the default
     * Role hasn't been seeded yet (app:permissions:seed), or if they
     * already hold it.
     */
    public function provisionDefaultForUser(User $user): void
    {
        if (!$user->getRoleEntities()->isEmpty()) {
            return;
        }

        $default = $this->findOneByName(self::DEFAULT_ROLE);
        if (!$default) {
            return;
        }

        $user->addRoleEntity($default);
        $this->getEntityManager()->flush();
    }
}
