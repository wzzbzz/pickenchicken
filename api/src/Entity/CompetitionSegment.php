<?php

namespace App\Entity;

use App\Repository\CompetitionSegmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompetitionSegmentRepository::class)]
class CompetitionSegment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Competition::class, inversedBy: 'segments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Competition $competition = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    /** E.g. "2026-01-15" — human label for a slate/day */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startsAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endsAt = null;

    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'segment', cascade: ['persist'])]
    #[ORM\OrderBy(['commenceTime' => 'ASC'])]
    private Collection $games;

    public function __construct()
    {
        $this->games = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getCompetition(): ?Competition { return $this->competition; }
    public function setCompetition(?Competition $competition): static { $this->competition = $competition; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getLabel(): ?string { return $this->label; }
    public function setLabel(?string $label): static { $this->label = $label; return $this; }

    public function getStartsAt(): ?\DateTimeImmutable { return $this->startsAt; }
    public function setStartsAt(?\DateTimeImmutable $startsAt): static { $this->startsAt = $startsAt; return $this; }

    public function getEndsAt(): ?\DateTimeImmutable { return $this->endsAt; }
    public function setEndsAt(?\DateTimeImmutable $endsAt): static { $this->endsAt = $endsAt; return $this; }

    public function getGames(): Collection { return $this->games; }
}
