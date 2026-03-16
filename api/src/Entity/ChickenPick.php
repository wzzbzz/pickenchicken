<?php

namespace App\Entity;

use App\Repository\ChickenPickRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChickenPickRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_chicken_pick_game', columns: ['game_id'])]
class ChickenPick
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TournamentGame $game = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?MarketOutcome $outcome = null;

    // When the chicken picked — i.e. when the market was locked
    #[ORM\Column]
    private ?\DateTimeImmutable $lockedAt = null;

    public function getId(): ?int { return $this->id; }

    public function getGame(): ?TournamentGame { return $this->game; }
    public function setGame(?TournamentGame $game): static { $this->game = $game; return $this; }

    public function getOutcome(): ?MarketOutcome { return $this->outcome; }
    public function setOutcome(?MarketOutcome $outcome): static { $this->outcome = $outcome; return $this; }

    public function getLockedAt(): ?\DateTimeImmutable { return $this->lockedAt; }
    public function setLockedAt(\DateTimeImmutable $lockedAt): static { $this->lockedAt = $lockedAt; return $this; }
}
