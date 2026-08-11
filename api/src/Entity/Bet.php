<?php

namespace App\Entity;

use App\Repository\BetRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A stake attached to a UserPick — the user's most-confident subset of
 * picks, distinct from the free Pick every game is encouraged to have.
 * `bankroll` is nullable: a bet can be journaled with just a stake/price,
 * with no bankroll accounting attached.
 */
#[ORM\Entity(repositoryClass: BetRepository::class)]
#[ORM\UniqueConstraint(columns: ['pick_id'])]
class Bet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToOne(targetEntity: UserPick::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserPick $pick = null;

    #[ORM\ManyToOne(targetEntity: Bankroll::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Bankroll $bankroll = null;

    #[ORM\Column]
    private int $stake = 0;

    /** American odds price at the time the bet was placed */
    #[ORM\Column(nullable: true)]
    private ?int $priceTaken = null;

    /** win | loss | push | null (pending) */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $result = null;

    #[ORM\Column(nullable: true)]
    private ?int $payout = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $settledAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getPick(): ?UserPick { return $this->pick; }
    public function setPick(?UserPick $pick): static { $this->pick = $pick; return $this; }

    public function getBankroll(): ?Bankroll { return $this->bankroll; }
    public function setBankroll(?Bankroll $bankroll): static { $this->bankroll = $bankroll; return $this; }

    public function getStake(): int { return $this->stake; }
    public function setStake(int $stake): static { $this->stake = $stake; return $this; }

    public function getPriceTaken(): ?int { return $this->priceTaken; }
    public function setPriceTaken(?int $priceTaken): static { $this->priceTaken = $priceTaken; return $this; }

    public function getResult(): ?string { return $this->result; }
    public function setResult(?string $result): static { $this->result = $result; return $this; }

    public function getPayout(): ?int { return $this->payout; }
    public function setPayout(?int $payout): static { $this->payout = $payout; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getSettledAt(): ?\DateTimeImmutable { return $this->settledAt; }
    public function setSettledAt(?\DateTimeImmutable $settledAt): static { $this->settledAt = $settledAt; return $this; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'pick_id' => $this->pick?->getId(),
            'bankroll_id' => $this->bankroll?->getId(),
            'stake' => $this->stake,
            'price_taken' => $this->priceTaken,
            'result' => $this->result,
            'payout' => $this->payout,
            'created_at' => $this->createdAt->format('c'),
            'settled_at' => $this->settledAt?->format('c'),
        ];
    }
}
