<?php

namespace App\Entity;

use App\Repository\CompetitionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompetitionRepository::class)]
class Competition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    private ?string $sportKey = null;

    /** open | active | closed */
    #[ORM\Column(length: 20)]
    private string $status = 'open';

    /** Defeat condition type: single_day | series_N | record_pct | season */
    #[ORM\Column(length: 20)]
    private string $defeatConditionType = 'single_day';

    /** Serialised defeat condition config (e.g. {"series": 3} or {"pct": 0.40, "min_games": 10}) */
    #[ORM\Column(type: 'json')]
    private array $defeatConditionConfig = [];

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startsAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endsAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: CompetitionSegment::class, mappedBy: 'competition', cascade: ['persist'])]
    #[ORM\OrderBy(['startsAt' => 'ASC'])]
    private Collection $segments;

    #[ORM\OneToMany(targetEntity: PlayerProgress::class, mappedBy: 'competition', cascade: ['persist'])]
    private Collection $playerProgresses;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->segments = new ArrayCollection();
        $this->playerProgresses = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getSportKey(): ?string { return $this->sportKey; }
    public function setSportKey(string $sportKey): static { $this->sportKey = $sportKey; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getDefeatConditionType(): string { return $this->defeatConditionType; }
    public function setDefeatConditionType(string $defeatConditionType): static { $this->defeatConditionType = $defeatConditionType; return $this; }

    public function getDefeatConditionConfig(): array { return $this->defeatConditionConfig; }
    public function setDefeatConditionConfig(array $defeatConditionConfig): static { $this->defeatConditionConfig = $defeatConditionConfig; return $this; }

    public function getStartsAt(): ?\DateTimeImmutable { return $this->startsAt; }
    public function setStartsAt(?\DateTimeImmutable $startsAt): static { $this->startsAt = $startsAt; return $this; }

    public function getEndsAt(): ?\DateTimeImmutable { return $this->endsAt; }
    public function setEndsAt(?\DateTimeImmutable $endsAt): static { $this->endsAt = $endsAt; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getSegments(): Collection { return $this->segments; }
    public function getPlayerProgresses(): Collection { return $this->playerProgresses; }
}
