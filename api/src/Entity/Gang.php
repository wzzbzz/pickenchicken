<?php

namespace App\Entity;

use App\Repository\GangRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GangRepository::class)]
class Gang
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'gang')]
    private Collection $Members;

    public function __construct()
    {
        $this->Members = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, User>
     */
    public function getMembers(): Collection
    {
        return $this->Members;
    }

    public function addMember(User $member): static
    {
        if (!$this->Members->contains($member)) {
            $this->Members->add($member);
            $member->setGang($this);
        }

        return $this;
    }

    public function removeMember(User $member): static
    {
        if ($this->Members->removeElement($member)) {
            // set the owning side to null (unless already changed)
            if ($member->getGang() === $this) {
                $member->setGang(null);
            }
        }

        return $this;
    }
}
