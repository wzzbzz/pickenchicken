<?php

namespace App\Repository;

use App\Entity\BotSubscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BotSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BotSubscription::class);
    }

    public function findActiveForUser(User $user): ?BotSubscription
    {
        return $this->findOneBy(['user' => $user, 'isActive' => true]);
    }

    public function findAllForUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }

    /**
     * Deactivates the current active subscription (if any) and activates/creates
     * the one for $botId. Reactivates a prior row for the same bot instead of
     * duplicating it.
     */
    public function subscribe(User $user, string $botId): BotSubscription
    {
        $em = $this->getEntityManager();

        $current = $this->findActiveForUser($user);
        if ($current && $current->getBotId() === $botId) {
            return $current;
        }
        if ($current) {
            $current->deactivate();
        }

        $existing = $this->findOneBy(['user' => $user, 'botId' => $botId]);
        if ($existing) {
            $existing->activate();
            $em->flush();
            return $existing;
        }

        $subscription = new BotSubscription($user, $botId);
        $em->persist($subscription);
        $em->flush();

        return $subscription;
    }
}
