<?php

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
class Location
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 30, unique: true)]
    private string $slug;

    #[ORM\Column(length: 80)]
    private string $name;

    /** Shown on first arrival */
    #[ORM\Column(name: 'long_desc', type: 'text')]
    private string $longDesc;

    /** Shown on return visits */
    #[ORM\Column(name: 'short_desc', type: 'text', nullable: true)]
    private ?string $shortDesc = null;

    /** Atmospheric one-liner shown alongside the description */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $atmosphere = null;

    /** Zone key matching PlayerProgress::zone, null for pre-game locations */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $zone = null;

    #[ORM\Column(nullable: true)]
    private ?int $minLadderPosition = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxLadderPosition = null;

    #[ORM\Column]
    private int $sortOrder = 0;

    public function getId(): int { return $this->id; }
    public function setId(int $id): static { $this->id = $id; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getLongDesc(): string { return $this->longDesc; }
    public function setLongDesc(string $longDesc): static { $this->longDesc = $longDesc; return $this; }

    public function getShortDesc(): ?string { return $this->shortDesc; }
    public function setShortDesc(?string $shortDesc): static { $this->shortDesc = $shortDesc; return $this; }

    public function getAtmosphere(): ?string { return $this->atmosphere; }
    public function setAtmosphere(?string $atmosphere): static { $this->atmosphere = $atmosphere; return $this; }

    public function getZone(): ?string { return $this->zone; }
    public function setZone(?string $zone): static { $this->zone = $zone; return $this; }

    public function getMinLadderPosition(): ?int { return $this->minLadderPosition; }
    public function setMinLadderPosition(?int $minLadderPosition): static { $this->minLadderPosition = $minLadderPosition; return $this; }

    public function getMaxLadderPosition(): ?int { return $this->maxLadderPosition; }
    public function setMaxLadderPosition(?int $maxLadderPosition): static { $this->maxLadderPosition = $maxLadderPosition; return $this; }

    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): static { $this->sortOrder = $sortOrder; return $this; }
}
