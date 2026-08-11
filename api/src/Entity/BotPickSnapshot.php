<?php

namespace App\Entity;

use App\Repository\BotPickSnapshotRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Records every bot's pick for a game at lock time, independent of whether any user challenged that bot.
 * This makes bot win% calculation accurate and auditable.
 */
#[ORM\Entity(repositoryClass: BotPickSnapshotRepository::class)]
#[ORM\UniqueConstraint(columns: ['game_id', 'bot_id'])]
class BotPickSnapshot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'botPickSnapshots')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\Column(length: 50)]
    private ?string $botId = null;

    /** home | away */
    #[ORM\Column(length: 10)]
    private ?string $pick = null;

    /** Bot's win% at lock time */
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $signalStrength = null;

    /** Raw signal metadata from odds-warehouse at lock time */
    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    /** win | loss | push | null (pending) */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $result = null;

    #[ORM\Column]
    private \DateTimeImmutable $lockedAt;

    public function __construct()
    {
        $this->lockedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getGame(): ?Game { return $this->game; }
    public function setGame(?Game $game): static { $this->game = $game; return $this; }

    public function getBotId(): ?string { return $this->botId; }
    public function setBotId(string $botId): static { $this->botId = $botId; return $this; }

    public function getPick(): ?string { return $this->pick; }
    public function setPick(string $pick): static { $this->pick = $pick; return $this; }

    public function getSignalStrength(): ?string { return $this->signalStrength; }
    public function setSignalStrength(?string $signalStrength): static { $this->signalStrength = $signalStrength; return $this; }

    public function getMetadata(): array { return $this->metadata; }
    public function setMetadata(array $metadata): static { $this->metadata = $metadata; return $this; }

    public function getResult(): ?string { return $this->result; }
    public function setResult(?string $result): static { $this->result = $result; return $this; }

    public function getLockedAt(): \DateTimeImmutable { return $this->lockedAt; }
    public function setLockedAt(\DateTimeImmutable $lockedAt): static { $this->lockedAt = $lockedAt; return $this; }
}
