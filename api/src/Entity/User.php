<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50, unique: true, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(nullable: true)]
    private ?string $passwordHash = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $loginToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $loginTokenExpiresAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: Session::class, mappedBy: 'user')]
    private Collection $sessions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->sessions = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUsername(): ?string { return $this->username; }
    public function setUsername(?string $username): static { $this->username = $username; return $this; }

    public function getUserIdentifier(): string { return $this->email ?? ''; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getLoginToken(): ?string { return $this->loginToken; }
    public function setLoginToken(?string $loginToken): static { $this->loginToken = $loginToken; return $this; }

    public function getLoginTokenExpiresAt(): ?\DateTimeImmutable { return $this->loginTokenExpiresAt; }
    public function setLoginTokenExpiresAt(?\DateTimeImmutable $loginTokenExpiresAt): static { $this->loginTokenExpiresAt = $loginTokenExpiresAt; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getPassword(): ?string { return $this->passwordHash; }
    public function setPasswordHash(?string $passwordHash): static { $this->passwordHash = $passwordHash; return $this; }
    public function hasPassword(): bool { return $this->passwordHash !== null; }

    public function eraseCredentials(): void { $this->loginToken = null; }

    public function getSessions(): Collection { return $this->sessions; }
}
