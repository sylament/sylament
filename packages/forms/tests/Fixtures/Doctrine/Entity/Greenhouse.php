<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A generated-id owner of {@see Planter}s, which its flush inserts through the
 * cascade.
 */
#[ORM\Entity]
class Greenhouse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40)]
    private string $name = '';

    /**
     * @var Collection<int, Planter>
     */
    #[ORM\OneToMany(targetEntity: Planter::class, mappedBy: 'greenhouse', cascade: ['persist'], orphanRemoval: true)]
    private Collection $planters;

    public function __construct()
    {
        $this->planters = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Collection<int, Planter>
     */
    public function getPlanters(): Collection
    {
        return $this->planters;
    }

    public function addPlanter(Planter $planter): void
    {
        if (! $this->planters->contains($planter)) {
            $this->planters->add($planter);
            $planter->setGreenhouse($this);
        }
    }

    public function removePlanter(Planter $planter): void
    {
        if ($this->planters->removeElement($planter)) {
            $planter->setGreenhouse(null);
        }
    }
}
