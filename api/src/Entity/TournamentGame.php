<?php

namespace App\Entity;

use App\Repository\TournamentGameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentGameRepository::class)]
class TournamentGame
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TournamentRound $round = null;

    #[ORM\Column(length: 128)]
    private ?string $homeTeam = null;

    #[ORM\Column(length: 128)]
    private ?string $awayTeam = null;

    #[ORM\Column(nullable: true)]
    private ?int $homeTeamSeed = null;

    #[ORM\Column(nullable: true)]
    private ?int $awayTeamSeed = null;

    // East | West | South | Midwest | Final Four | Play-In
    #[ORM\Column(length: 32, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $commenceTime = null;

    // scheduled | in_progress | final
    #[ORM\Column(length: 20)]
    private string $status = 'scheduled';

    // Team name of the winner, null until final
    #[ORM\Column(length: 128, nullable: true)]
    private ?string $winner = null;

    // ESPN's external game ID for syncing
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $espnGameId = null;

    // Final scores — set when status = final
    #[ORM\Column(nullable: true)]
    private ?int $homeScore = null;

    #[ORM\Column(nullable: true)]
    private ?int $awayScore = null;

    /**
     * @var Collection<int, Pick>
     */
    #[ORM\OneToMany(targetEntity: Pick::class, mappedBy: 'game', orphanRemoval: true)]
    private Collection $picks;

    public function __construct()
    {
        $this->picks = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getRound(): ?TournamentRound { return $this->round; }
    public function setRound(?TournamentRound $round): static { $this->round = $round; return $this; }

    public function getHomeTeam(): ?string { return $this->homeTeam; }
    public function setHomeTeam(string $homeTeam): static { $this->homeTeam = $homeTeam; return $this; }

    public function getAwayTeam(): ?string { return $this->awayTeam; }
    public function setAwayTeam(string $awayTeam): static { $this->awayTeam = $awayTeam; return $this; }

    public function getHomeTeamSeed(): ?int { return $this->homeTeamSeed; }
    public function setHomeTeamSeed(?int $seed): static { $this->homeTeamSeed = $seed; return $this; }

    public function getAwayTeamSeed(): ?int { return $this->awayTeamSeed; }
    public function setAwayTeamSeed(?int $seed): static { $this->awayTeamSeed = $seed; return $this; }

    public function getRegion(): ?string { return $this->region; }
    public function setRegion(?string $region): static { $this->region = $region; return $this; }

    public function getCommenceTime(): ?\DateTimeImmutable { return $this->commenceTime; }
    public function setCommenceTime(?\DateTimeImmutable $commenceTime): static { $this->commenceTime = $commenceTime; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getWinner(): ?string { return $this->winner; }
    public function setWinner(?string $winner): static { $this->winner = $winner; return $this; }

    public function getEspnGameId(): ?string { return $this->espnGameId; }
    public function setEspnGameId(?string $espnGameId): static { $this->espnGameId = $espnGameId; return $this; }

    public function getHomeScore(): ?int { return $this->homeScore; }
    public function setHomeScore(?int $homeScore): static { $this->homeScore = $homeScore; return $this; }

    public function getAwayScore(): ?int { return $this->awayScore; }
    public function setAwayScore(?int $awayScore): static { $this->awayScore = $awayScore; return $this; }

    /** @return Collection<int, Pick> */
    public function getPicks(): Collection { return $this->picks; }
}
