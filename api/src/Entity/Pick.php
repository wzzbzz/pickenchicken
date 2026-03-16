<?php

namespace App\Entity;

use App\Repository\PickRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PickRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_user_game', columns: ['user_id', 'game_id'])]
class Pick
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'picks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TournamentGame $game = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?MarketOutcome $userOutcome = null;

    // Denormalised for easy querying
    #[ORM\Column(length: 64)]
    private ?string $marketKey = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    // null until game is final: user_wins | chicken_wins | tie_win | tie_loss
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $result = null;

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getGame(): ?TournamentGame { return $this->game; }
    public function setGame(?TournamentGame $game): static { $this->game = $game; return $this; }

    public function getUserOutcome(): ?MarketOutcome { return $this->userOutcome; }
    public function setUserOutcome(?MarketOutcome $outcome): static { $this->userOutcome = $outcome; return $this; }

    public function getMarketKey(): ?string { return $this->marketKey; }
    public function setMarketKey(string $marketKey): static { $this->marketKey = $marketKey; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getResult(): ?string { return $this->result; }
    public function setResult(?string $result): static { $this->result = $result; return $this; }
}
