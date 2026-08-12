<?php

namespace App\Entity;

use App\Repository\PermissionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A single named capability. Its `key` maps deterministically onto a
 * Symfony role string (`place_bets` -> `ROLE_PLACE_BETS`) — see
 * User::getRoles() — so existing access_control/denyAccessUnlessGranted
 * checks work against Permissions with zero changes to how they're wired.
 */
#[ORM\Entity(repositoryClass: PermissionRepository::class)]
class Permission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $key = null;

    #[ORM\Column(length: 100)]
    private ?string $label = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getKey(): ?string { return $this->key; }
    public function setKey(string $key): static { $this->key = $key; return $this; }

    public function getLabel(): ?string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** The Symfony role string this permission grants, e.g. ROLE_PLACE_BETS. */
    public function toRoleString(): string
    {
        return 'ROLE_' . strtoupper($this->key);
    }
}
