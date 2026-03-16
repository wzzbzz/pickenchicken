<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Session $session = null;

    #[ORM\Column(length: 100)]
    private ?string $eventType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, EventProperty>
     */
    #[ORM\OneToMany(targetEntity: EventProperty::class, mappedBy: 'event')]
    private Collection $eventProperties;

    public function __construct()
    {
        $this->setCreatedAt(new \DateTimeImmutable());
        $this->eventProperties = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): static
    {
        $this->session = $session;

        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

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
     * @return Collection<int, EventProperty>
     */
    public function getEventProperties(): Collection
    {
        return $this->eventProperties;
    }

    public function addEventProperty(EventProperty $eventProperty): static
    {
        if (!$this->eventProperties->contains($eventProperty)) {
            $this->eventProperties->add($eventProperty);
            $eventProperty->setEvent($this);
        }

        return $this;
    }

    public function removeEventProperty(EventProperty $eventProperty): static
    {
        if ($this->eventProperties->removeElement($eventProperty)) {
            // set the owning side to null (unless already changed)
            if ($eventProperty->getEvent() === $this) {
                $eventProperty->setEvent(null);
            }
        }

        return $this;
    }
}
