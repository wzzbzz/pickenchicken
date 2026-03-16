<?php

namespace App\Entity;

use App\Repository\MarketOutcomeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MarketOutcomeRepository::class)]
class MarketOutcome
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'outcomes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?GameMarket $market = null;

    // Team name, "Over", "Under", or player name
    #[ORM\Column(length: 128)]
    private ?string $name = null;

    // For player props: the player's name
    #[ORM\Column(length: 128, nullable: true)]
    private ?string $description = null;

    // American odds price e.g. -110, +120
    #[ORM\Column]
    private ?int $price = null;

    // Spread or total line e.g. -6.5, 142.5 (null for h2h)
    #[ORM\Column(nullable: true)]
    private ?float $point = null;

    // Human-readable label e.g. "Auburn Tigers -6.5" or "Over 142.5"
    #[ORM\Column(length: 128)]
    private ?string $label = null;

    public function getId(): ?int { return $this->id; }

    public function getMarket(): ?GameMarket { return $this->market; }
    public function setMarket(?GameMarket $market): static { $this->market = $market; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getPrice(): ?int { return $this->price; }
    public function setPrice(int $price): static { $this->price = $price; return $this; }

    public function getPoint(): ?float { return $this->point; }
    public function setPoint(?float $point): static { $this->point = $point; return $this; }

    public function getLabel(): ?string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }
}
