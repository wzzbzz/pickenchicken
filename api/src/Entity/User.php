<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`app_user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $loginToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $loginTokenExpiresAt = null;

    #[ORM\Column(type: 'blob', nullable: true)]
    private $selectionHistory = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $selectionCount = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Session>
     */
    #[ORM\OneToMany(targetEntity: Session::class, mappedBy: 'app_user')]
    private Collection $sessions;

    #[ORM\ManyToOne(inversedBy: 'Members')]
    private ?Gang $gang = null;

    public function __construct()
    {
        $this->sessions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, Session>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(Session $session): static
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setAppUser($this);
        }

        return $this;
    }

    public function removeSession(Session $session): static
    {
        if ($this->sessions->removeElement($session)) {
            // set the owning side to null (unless already changed)
            if ($session->getAppUser() === $this) {
                $session->setAppUser(null);
            }
        }

        return $this;
    }

    public function getGang(): ?Gang
    {
        return $this->gang;
    }

    public function setGang(?Gang $gang): static
    {
        $this->gang = $gang;

        return $this;
    }

    public function getLoginToken(): ?string
    {
        return $this->loginToken;
    }

    public function setLoginToken(?string $loginToken): static
    {
        $this->loginToken = $loginToken;

        return $this;
    }

    public function getLoginTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->loginTokenExpiresAt;
    }

    public function setLoginTokenExpiresAt(?\DateTimeImmutable $loginTokenExpiresAt): static
    {
        $this->loginTokenExpiresAt = $loginTokenExpiresAt;

        return $this;
    }

    public function getSelectionHistory()
    {
        return $this->selectionHistory;
    }

    public function setSelectionHistory($selectionHistory): static
    {
        $this->selectionHistory = $selectionHistory;

        return $this;
    }

    public function getSelectionCount(): ?int
    {
        return $this->selectionCount;
    }

    public function setSelectionCount(?int $selectionCount): static
    {
        $this->selectionCount = $selectionCount;

        return $this;
    }

    public function toString(): string
    {
        return sprintf(
            'User{id=%d, username=%s, email=%s, loginToken=%s, loginTokenExpiresAt=%s, createdAt=%s}',
            $this->id,
            $this->username ?? 'null',
            $this->email ?? 'null',
            $this->loginToken ?? 'null',
            $this->loginTokenExpiresAt ? $this->loginTokenExpiresAt->format('Y-m-d H:i:s') : 'null',
            $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : 'null'
        );
    }
}
