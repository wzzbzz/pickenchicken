<?php
// src/Entity/Challenge.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Challenge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    private string $code;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?string $player1 = null;

    #[ORM\Column(nullable: true)]
    private ?string $player2 = null;

    #[ORM\Column(length: 20)]
    private string $status = 'waiting'; // waiting | active | finished

    public function __construct(string $code)
    {
        $this->code = $code;
        $this->createdAt = new \DateTimeImmutable();
    }

    // getters & setters...
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getCode(): string
    {
        return $this->code;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getPlayer1(): ?string
    {
        return $this->player1;
    }
    public function setPlayer1(?string $player1): self
    {
        $this->player1 = $player1;
        return $this;
    }
    public function getPlayer2(): ?string
    {
        return $this->player2;
    }
    public function setPlayer2(?string $player2): self
    {
        $this->player2 = $player2;
        return $this;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }
}
?>
