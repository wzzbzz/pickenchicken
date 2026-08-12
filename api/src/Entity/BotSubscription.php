<?php

namespace App\Entity;

use App\Repository\BotSubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BotSubscriptionRepository::class)]
class BotSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    /** Free-text bot name from odds-warehouse, unvalidated — same trust level as BotPickSnapshot::$botId. */
    #[ORM\Column(length: 50)]
    private string $botId;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, string $botId)
    {
        $this->user = $user;
        $this->botId = $botId;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }

    public function getBotId(): string { return $this->botId; }

    public function isActive(): bool { return $this->isActive; }
    public function activate(): static { $this->isActive = true; return $this; }
    public function deactivate(): static { $this->isActive = false; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function toArray(): array
    {
        return [
            'bot_id' => $this->botId,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt->format(DATE_ATOM),
        ];
    }
}
