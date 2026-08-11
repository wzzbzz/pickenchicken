<?php

namespace App\Entity;

use App\Repository\WalletRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WalletRepository::class)]
class Wallet
{
    private const STARTING_BALANCE = 100;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'integer')]
    private int $balance = self::STARTING_BALANCE;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getBalance(): int { return $this->balance; }

    public function credit(int $amount): void { $this->balance += $amount; }
    public function debit(int $amount): void  { $this->balance -= $amount; }

    public function toArray(): array
    {
        return [
            'balance'    => $this->balance,
            'currency'   => 'CluckBucks',
        ];
    }
}
