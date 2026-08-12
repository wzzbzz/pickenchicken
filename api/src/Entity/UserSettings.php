<?php

namespace App\Entity;

use App\Repository\UserSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSettingsRepository::class)]
class UserSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nickname = null;

    #[ORM\Column(nullable: true)]
    private ?string $avatarUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $personalStatement = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $pickStyle = null;

    /** human | bot */
    #[ORM\Column(length: 10)]
    private string $accountType = 'human';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): static { $this->name = $name; return $this; }

    public function getNickname(): ?string { return $this->nickname; }
    public function setNickname(?string $nickname): static { $this->nickname = $nickname; return $this; }

    public function getAvatarUrl(): ?string { return $this->avatarUrl; }
    public function setAvatarUrl(?string $avatarUrl): static { $this->avatarUrl = $avatarUrl; return $this; }

    public function getPersonalStatement(): ?string { return $this->personalStatement; }
    public function setPersonalStatement(?string $personalStatement): static { $this->personalStatement = $personalStatement; return $this; }

    public function getPickStyle(): ?string { return $this->pickStyle; }
    public function setPickStyle(?string $pickStyle): static { $this->pickStyle = $pickStyle; return $this; }

    public function getAccountType(): string { return $this->accountType; }
    public function setAccountType(string $accountType): static { $this->accountType = $accountType; return $this; }
    public function isBot(): bool { return $this->accountType === 'bot'; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'nickname' => $this->nickname,
            'avatar_url' => $this->avatarUrl,
            'personal_statement' => $this->personalStatement,
            'pick_style' => $this->pickStyle,
            'account_type' => $this->accountType,
        ];
    }
}
