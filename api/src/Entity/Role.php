<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A named bundle of Permissions. A User can hold multiple Roles at once
 * (see User::$userRoles) — e.g. a base "free" Role plus an independent
 * add-on Role — rather than a single fixed tier per user.
 */
#[ORM\Entity(repositoryClass: RoleRepository::class)]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $label = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToMany(targetEntity: Permission::class)]
    #[ORM\JoinTable(name: 'role_permission')]
    private Collection $permissions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->permissions = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getLabel(): ?string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getPermissions(): Collection { return $this->permissions; }

    public function addPermission(Permission $permission): static
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
        }
        return $this;
    }
}
