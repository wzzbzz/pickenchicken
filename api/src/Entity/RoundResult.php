<?php

namespace App\Entity;

use App\Repository\RoundResultRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoundResultRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_user_round', columns: ['user_id', 'round_id'])]
class RoundResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TournamentRound $round = null;

    // How many games this user beat the chicken in this round
    #[ORM\Column]
    private int $beatenChickenCount = 0;

    #[ORM\Column]
    private bool $isRoundWinner = false;

    // When this result was last computed
    #[ORM\Column]
    private ?\DateTimeImmutable $computedAt = null;

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getRound(): ?TournamentRound { return $this->round; }
    public function setRound(?TournamentRound $round): static { $this->round = $round; return $this; }

    public function getBeatenChickenCount(): int { return $this->beatenChickenCount; }
    public function setBeatenChickenCount(int $count): static { $this->beatenChickenCount = $count; return $this; }

    public function isRoundWinner(): bool { return $this->isRoundWinner; }
    public function setIsRoundWinner(bool $isRoundWinner): static { $this->isRoundWinner = $isRoundWinner; return $this; }

    public function getComputedAt(): ?\DateTimeImmutable { return $this->computedAt; }
    public function setComputedAt(\DateTimeImmutable $computedAt): static { $this->computedAt = $computedAt; return $this; }
}
