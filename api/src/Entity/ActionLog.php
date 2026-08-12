<?php

namespace App\Entity;

use App\Repository\ActionLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * One row per request that reached a RequiresPermission-attributed
 * controller action — written by PermissionCheckListener, whether access
 * was granted or denied.
 */
#[ORM\Entity(repositoryClass: ActionLogRepository::class)]
class ActionLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $permission = null;

    #[ORM\Column(length: 255)]
    private ?string $path = null;

    #[ORM\Column(length: 10)]
    private ?string $method = null;

    #[ORM\Column]
    private bool $granted = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getPermission(): ?string { return $this->permission; }
    public function setPermission(?string $permission): static { $this->permission = $permission; return $this; }

    public function getPath(): ?string { return $this->path; }
    public function setPath(string $path): static { $this->path = $path; return $this; }

    public function getMethod(): ?string { return $this->method; }
    public function setMethod(string $method): static { $this->method = $method; return $this; }

    public function isGranted(): bool { return $this->granted; }
    public function setGranted(bool $granted): static { $this->granted = $granted; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
