<?php

namespace App\Entity;

use App\Repository\ChallengerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChallengerRepository::class)]
class Challenger
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $sessionToken = null;

    #[ORM\Column(nullable: true)]
    private ?int $bestStreak = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSessionToken(): ?string
    {
        return $this->sessionToken;
    }

    public function setSessionToken(string $sessionToken): static
    {
        $this->sessionToken = $sessionToken;

        return $this;
    }

    public function getBestStreak(): ?int
    {
        return $this->bestStreak;
    }

    public function setBestStreak(?int $bestStreak): static
    {
        $this->bestStreak = $bestStreak;

        return $this;
    }
}
