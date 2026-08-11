<?php

namespace App\Entity;

use App\Repository\UserPickRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPickRepository::class)]
#[ORM\UniqueConstraint(columns: ['user_id', 'game_id'])]
class UserPick
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'userPicks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    /** home | away */
    #[ORM\Column(length: 10)]
    private ?string $pick = null;

    /** win | loss | push | null (pending) */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $result = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lockedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getGame(): ?Game { return $this->game; }
    public function setGame(?Game $game): static { $this->game = $game; return $this; }

    public function getPick(): ?string { return $this->pick; }
    public function setPick(string $pick): static { $this->pick = $pick; return $this; }

    public function getResult(): ?string { return $this->result; }
    public function setResult(?string $result): static { $this->result = $result; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getLockedAt(): ?\DateTimeImmutable { return $this->lockedAt; }
    public function setLockedAt(?\DateTimeImmutable $lockedAt): static { $this->lockedAt = $lockedAt; return $this; }
}
