<?php

namespace App\Entity;

use App\Repository\AppConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppConfigRepository::class)]
#[ORM\Table(name: 'app_config')]
class AppConfig
{
    #[ORM\Id]
    #[ORM\Column(length: 64)]
    private string $key;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $value = null;

    public function __construct(string $key, ?string $value = null)
    {
        $this->key   = $key;
        $this->value = $value;
    }

    public function getKey(): string { return $this->key; }

    public function getValue(): ?string { return $this->value; }
    public function setValue(?string $value): static { $this->value = $value; return $this; }
}
