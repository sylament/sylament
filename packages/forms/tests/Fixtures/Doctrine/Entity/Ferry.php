<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SortDirection;

/**
 * An owner whose related records carry a date and an enum — leaves a
 * relationship repeater item and a singular relationship fill only through the
 * state casts that flatten them.
 */
#[ORM\Entity]
class Ferry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $name = '';

    /**
     * @var Collection<int, Crossing>
     */
    #[ORM\OneToMany(targetEntity: Crossing::class, mappedBy: 'ferry', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => SortDirection::Ascending])]
    private Collection $crossings;

    #[ORM\OneToOne(cascade: ['persist'])]
    private ?Berth $berth = null;

    public function __construct()
    {
        $this->crossings = new ArrayCollection();
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
     * @return Collection<int, Crossing>
     */
    public function getCrossings(): Collection
    {
        return $this->crossings;
    }

    public function addCrossing(Crossing $crossing): void
    {
        if (! $this->crossings->contains($crossing)) {
            $this->crossings->add($crossing);
            $crossing->setFerry($this);
        }
    }

    public function removeCrossing(Crossing $crossing): void
    {
        $this->crossings->removeElement($crossing);
    }

    public function getBerth(): ?Berth
    {
        return $this->berth;
    }

    public function setBerth(?Berth $berth): void
    {
        $this->berth = $berth;
    }
}
