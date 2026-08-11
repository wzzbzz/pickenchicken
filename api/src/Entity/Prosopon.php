<?php

namespace App\Entity;

use App\Repository\ProsoponRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProsoponRepository::class)]
class Prosopon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Location::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Location $location = null;

    /** bot | user */
    #[ORM\Column(length: 10)]
    private string $type;

    /** Bot key from odds-warehouse, e.g. 'the_chicken' */
    #[ORM\Column(length: 80, nullable: true)]
    private ?string $botKey = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(length: 80)]
    private string $displayName;

    /** Permanent prosopa (bots) are always present; user prosopa arrive and depart */
    #[ORM\Column]
    private bool $isPermanent = false;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $arrivedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSeenAt = null;

    public function getId(): ?int { return $this->id; }

    public function getLocation(): ?Location { return $this->location; }
    public function setLocation(?Location $location): static { $this->location = $location; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getBotKey(): ?string { return $this->botKey; }
    public function setBotKey(?string $botKey): static { $this->botKey = $botKey; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getDisplayName(): string { return $this->displayName; }
    public function setDisplayName(string $displayName): static { $this->displayName = $displayName; return $this; }

    public function isPermanent(): bool { return $this->isPermanent; }
    public function setIsPermanent(bool $isPermanent): static { $this->isPermanent = $isPermanent; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function getArrivedAt(): ?\DateTimeImmutable { return $this->arrivedAt; }
    public function setArrivedAt(?\DateTimeImmutable $arrivedAt): static { $this->arrivedAt = $arrivedAt; return $this; }

    public function getLastSeenAt(): ?\DateTimeImmutable { return $this->lastSeenAt; }
    public function setLastSeenAt(?\DateTimeImmutable $lastSeenAt): static { $this->lastSeenAt = $lastSeenAt; return $this; }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'bot_key'      => $this->botKey,
            'display_name' => $this->displayName,
            'is_permanent' => $this->isPermanent,
        ];
    }
}
