<?php

namespace App\Entity;

use App\Repository\BankrollRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A Daybook journal bankroll — distinct from Wallet (PickenChicken's fixed
 * "CluckBucks" ladder-game balance). A user may have at most one real and
 * one "fae" (play-money) bankroll, each with its own configurable starting
 * balance, per Bet.
 */
#[ORM\Entity(repositoryClass: BankrollRepository::class)]
#[ORM\UniqueConstraint(columns: ['user_id', 'is_fae'])]
class Bankroll
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private bool $isFae = false;

    #[ORM\Column]
    private int $startingBalance = 0;

    #[ORM\Column]
    private int $currentBalance = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(User $user, int $startingBalance, bool $isFae = false)
    {
        $this->user = $user;
        $this->isFae = $isFae;
        $this->startingBalance = $startingBalance;
        $this->currentBalance = $startingBalance;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }

    public function isFae(): bool { return $this->isFae; }

    public function getStartingBalance(): int { return $this->startingBalance; }

    public function getCurrentBalance(): int { return $this->currentBalance; }

    public function credit(int $amount): void
    {
        $this->currentBalance += $amount;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function debit(int $amount): void
    {
        $this->currentBalance -= $amount;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'is_fae' => $this->isFae,
            'starting_balance' => $this->startingBalance,
            'current_balance' => $this->currentBalance,
        ];
    }
}
