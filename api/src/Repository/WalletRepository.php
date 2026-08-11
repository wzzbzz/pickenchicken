<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Wallet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WalletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Wallet::class);
    }

    public function findByUser(User $user): ?Wallet
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function provisionForUser(User $user): Wallet
    {
        $wallet = $this->findByUser($user);
        if ($wallet) {
            return $wallet;
        }

        $wallet = new Wallet($user);
        $this->getEntityManager()->persist($wallet);
        $this->getEntityManager()->flush();

        return $wallet;
    }
}
