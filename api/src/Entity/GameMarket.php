<?php

namespace App\Entity;

use App\Repository\GameMarketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameMarketRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_game_market_bookmaker', columns: ['game_id', 'market_key', 'bookmaker'])]
class GameMarket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TournamentGame $game = null;

    // e.g. spreads | h2h | totals | player_points
    #[ORM\Column(length: 64)]
    private ?string $marketKey = null;

    // e.g. draftkings | fanduel | betmgm
    #[ORM\Column(length: 64)]
    private ?string $bookmaker = null;

    // The Odds API event ID — used to re-fetch this market
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $oddsApiEventId = null;

    // When we last fetched this market from The Odds API
    #[ORM\Column]
    private ?\DateTimeImmutable $fetchedAt = null;

    // When this market was locked — null means not yet locked
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lockedAt = null;

    /**
     * @var Collection<int, MarketOutcome>
     */
    #[ORM\OneToMany(targetEntity: MarketOutcome::class, mappedBy: 'market', orphanRemoval: true)]
    private Collection $outcomes;

    public function __construct()
    {
        $this->outcomes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getGame(): ?TournamentGame { return $this->game; }
    public function setGame(?TournamentGame $game): static { $this->game = $game; return $this; }

    public function getMarketKey(): ?string { return $this->marketKey; }
    public function setMarketKey(string $marketKey): static { $this->marketKey = $marketKey; return $this; }

    public function getBookmaker(): ?string { return $this->bookmaker; }
    public function setBookmaker(string $bookmaker): static { $this->bookmaker = $bookmaker; return $this; }

    public function getOddsApiEventId(): ?string { return $this->oddsApiEventId; }
    public function setOddsApiEventId(?string $id): static { $this->oddsApiEventId = $id; return $this; }

    public function getFetchedAt(): ?\DateTimeImmutable { return $this->fetchedAt; }
    public function setFetchedAt(\DateTimeImmutable $fetchedAt): static { $this->fetchedAt = $fetchedAt; return $this; }

    public function getLockedAt(): ?\DateTimeImmutable { return $this->lockedAt; }
    public function setLockedAt(?\DateTimeImmutable $lockedAt): static { $this->lockedAt = $lockedAt; return $this; }

    public function isLocked(): bool { return $this->lockedAt !== null; }

    /** @return Collection<int, MarketOutcome> */
    public function getOutcomes(): Collection { return $this->outcomes; }

    public function addOutcome(MarketOutcome $outcome): static
    {
        if (!$this->outcomes->contains($outcome)) {
            $this->outcomes->add($outcome);
            $outcome->setMarket($this);
        }
        return $this;
    }

    public function removeOutcome(MarketOutcome $outcome): static
    {
        if ($this->outcomes->removeElement($outcome)) {
            if ($outcome->getMarket() === $this) {
                $outcome->setMarket(null);
            }
        }
        return $this;
    }
}
