<?php

namespace App\Entity;

use App\Repository\PlayerProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerProgressRepository::class)]
#[ORM\UniqueConstraint(columns: ['user_id', 'competition_id'])]
class PlayerProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Competition::class, inversedBy: 'playerProgresses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Competition $competition = null;

    /** Current bot on the ladder the player is challenging */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $currentBotId = null;

    /** Zone: yard | coop | season_coop | barn | silo | council */
    #[ORM\Column(length: 20)]
    private string $zone = 'yard';

    /** Position within the 98-bot ladder (1–98) */
    #[ORM\Column]
    private int $ladderPosition = 1;

    /** Wins in current challenge series */
    #[ORM\Column]
    private int $seriesWins = 0;

    /** Losses in current challenge series */
    #[ORM\Column]
    private int $seriesLosses = 0;

    /** Total wins in this competition */
    #[ORM\Column]
    private int $totalWins = 0;

    /** Total losses in this competition */
    #[ORM\Column]
    private int $totalLosses = 0;

    /** Ordered list of defeated bot IDs with defeat timestamps */
    #[ORM\Column(type: 'json')]
    private array $defeatedBots = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getCompetition(): ?Competition { return $this->competition; }
    public function setCompetition(?Competition $competition): static { $this->competition = $competition; return $this; }

    public function getCurrentBotId(): ?string { return $this->currentBotId; }
    public function setCurrentBotId(?string $currentBotId): static { $this->currentBotId = $currentBotId; return $this; }

    public function getZone(): string { return $this->zone; }
    public function setZone(string $zone): static { $this->zone = $zone; return $this; }

    public function getLadderPosition(): int { return $this->ladderPosition; }
    public function setLadderPosition(int $ladderPosition): static { $this->ladderPosition = $ladderPosition; return $this; }

    public function getSeriesWins(): int { return $this->seriesWins; }
    public function setSeriesWins(int $seriesWins): static { $this->seriesWins = $seriesWins; return $this; }

    public function getSeriesLosses(): int { return $this->seriesLosses; }
    public function setSeriesLosses(int $seriesLosses): static { $this->seriesLosses = $seriesLosses; return $this; }

    public function getTotalWins(): int { return $this->totalWins; }
    public function setTotalWins(int $totalWins): static { $this->totalWins = $totalWins; return $this; }

    public function getTotalLosses(): int { return $this->totalLosses; }
    public function setTotalLosses(int $totalLosses): static { $this->totalLosses = $totalLosses; return $this; }

    public function getDefeatedBots(): array { return $this->defeatedBots; }
    public function setDefeatedBots(array $defeatedBots): static { $this->defeatedBots = $defeatedBots; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
}
