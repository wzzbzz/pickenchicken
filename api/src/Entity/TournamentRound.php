<?php

namespace App\Entity;

use App\Repository\TournamentRoundRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentRoundRepository::class)]
class TournamentRound
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $name = null;

    // 0 = play-in, 1 = Round of 64, 2 = Round of 32, etc.
    #[ORM\Column]
    private ?int $roundNumber = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startsAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endsAt = null;

    // upcoming | in_progress | complete
    #[ORM\Column(length: 20)]
    private string $status = 'upcoming';

    /**
     * @var Collection<int, TournamentGame>
     */
    #[ORM\OneToMany(targetEntity: TournamentGame::class, mappedBy: 'round', orphanRemoval: true)]
    private Collection $games;

    public function __construct()
    {
        $this->games = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getRoundNumber(): ?int { return $this->roundNumber; }
    public function setRoundNumber(int $roundNumber): static { $this->roundNumber = $roundNumber; return $this; }

    public function getStartsAt(): ?\DateTimeImmutable { return $this->startsAt; }
    public function setStartsAt(?\DateTimeImmutable $startsAt): static { $this->startsAt = $startsAt; return $this; }

    public function getEndsAt(): ?\DateTimeImmutable { return $this->endsAt; }
    public function setEndsAt(?\DateTimeImmutable $endsAt): static { $this->endsAt = $endsAt; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    /** @return Collection<int, TournamentGame> */
    public function getGames(): Collection { return $this->games; }

    public function addGame(TournamentGame $game): static
    {
        if (!$this->games->contains($game)) {
            $this->games->add($game);
            $game->setRound($this);
        }
        return $this;
    }

    public function removeGame(TournamentGame $game): static
    {
        if ($this->games->removeElement($game)) {
            if ($game->getRound() === $this) {
                $game->setRound(null);
            }
        }
        return $this;
    }
}
