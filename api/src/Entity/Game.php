<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CompetitionSegment::class, inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CompetitionSegment $segment = null;

    /** Odds API event ID — links to odds-warehouse */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $oddsApiEventId = null;

    #[ORM\Column(length: 100)]
    private ?string $homeTeam = null;

    #[ORM\Column(length: 100)]
    private ?string $awayTeam = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $commenceTime = null;

    /** scheduled | in_progress | complete | cancelled */
    #[ORM\Column(length: 20)]
    private string $status = 'scheduled';

    /** ATS spread value (positive = home favoured by N points) */
    #[ORM\Column(type: 'decimal', precision: 5, scale: 1, nullable: true)]
    private ?string $spread = null;

    /** American odds price for the home side, at last import */
    #[ORM\Column(nullable: true)]
    private ?int $homePrice = null;

    /** American odds price for the away side, at last import */
    #[ORM\Column(nullable: true)]
    private ?int $awayPrice = null;

    #[ORM\Column(nullable: true)]
    private ?int $homeScore = null;

    #[ORM\Column(nullable: true)]
    private ?int $awayScore = null;

    /** home | away | push | null */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $atsResult = null;

    /** Chicken's pick for this game: home | away | null (pre-lock) */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $chickenPick = null;

    /** Which odds-warehouse bot sourced the Chicken pick */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $chickenBotId = null;

    /** Bot win% at the time the pick was locked */
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $chickenSignalStrength = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lockedAt = null;

    #[ORM\OneToMany(targetEntity: UserPick::class, mappedBy: 'game', cascade: ['persist'])]
    private Collection $userPicks;

    #[ORM\OneToMany(targetEntity: BotPickSnapshot::class, mappedBy: 'game', cascade: ['persist'])]
    private Collection $botPickSnapshots;

    public function __construct()
    {
        $this->userPicks = new ArrayCollection();
        $this->botPickSnapshots = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getSegment(): ?CompetitionSegment { return $this->segment; }
    public function setSegment(?CompetitionSegment $segment): static { $this->segment = $segment; return $this; }

    public function getOddsApiEventId(): ?string { return $this->oddsApiEventId; }
    public function setOddsApiEventId(?string $oddsApiEventId): static { $this->oddsApiEventId = $oddsApiEventId; return $this; }

    public function getHomeTeam(): ?string { return $this->homeTeam; }
    public function setHomeTeam(string $homeTeam): static { $this->homeTeam = $homeTeam; return $this; }

    public function getAwayTeam(): ?string { return $this->awayTeam; }
    public function setAwayTeam(string $awayTeam): static { $this->awayTeam = $awayTeam; return $this; }

    public function getCommenceTime(): ?\DateTimeImmutable { return $this->commenceTime; }
    public function setCommenceTime(?\DateTimeImmutable $commenceTime): static { $this->commenceTime = $commenceTime; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getSpread(): ?string { return $this->spread; }
    public function setSpread(?string $spread): static { $this->spread = $spread; return $this; }

    public function getHomePrice(): ?int { return $this->homePrice; }
    public function setHomePrice(?int $homePrice): static { $this->homePrice = $homePrice; return $this; }

    public function getAwayPrice(): ?int { return $this->awayPrice; }
    public function setAwayPrice(?int $awayPrice): static { $this->awayPrice = $awayPrice; return $this; }

    public function getHomeScore(): ?int { return $this->homeScore; }
    public function setHomeScore(?int $homeScore): static { $this->homeScore = $homeScore; return $this; }

    public function getAwayScore(): ?int { return $this->awayScore; }
    public function setAwayScore(?int $awayScore): static { $this->awayScore = $awayScore; return $this; }

    public function getAtsResult(): ?string { return $this->atsResult; }
    public function setAtsResult(?string $atsResult): static { $this->atsResult = $atsResult; return $this; }

    public function getChickenPick(): ?string { return $this->chickenPick; }
    public function setChickenPick(?string $chickenPick): static { $this->chickenPick = $chickenPick; return $this; }

    public function getChickenBotId(): ?string { return $this->chickenBotId; }
    public function setChickenBotId(?string $chickenBotId): static { $this->chickenBotId = $chickenBotId; return $this; }

    public function getChickenSignalStrength(): ?string { return $this->chickenSignalStrength; }
    public function setChickenSignalStrength(?string $chickenSignalStrength): static { $this->chickenSignalStrength = $chickenSignalStrength; return $this; }

    public function getLockedAt(): ?\DateTimeImmutable { return $this->lockedAt; }
    public function setLockedAt(?\DateTimeImmutable $lockedAt): static { $this->lockedAt = $lockedAt; return $this; }

    public function getUserPicks(): Collection { return $this->userPicks; }
    public function getBotPickSnapshots(): Collection { return $this->botPickSnapshots; }
}
