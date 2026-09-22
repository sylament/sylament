<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by two scalar fields that owns a collection of its own kind
 * through a to-one named `owner` — the name a table's alias minter turns into
 * `sy_owner`, which the relationship subselect already holds — with `related`
 * beside it for the second such name. Reaching either takes no user code: a
 * dotted sort or a dotted summary over this class is enough.
 */
#[ORM\Entity]
class Circuit
{
    /**
     * A composite owner's children, re-pointable: the mapped-by to-one is
     * nullable and carries a setter, so one can be associated and dissociated,
     * and the mapping declares its own order — the arm a relation-scoped table
     * reads a default sort off.
     *
     * @var Collection<int, Circuit>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'owner')]
    #[ORM\OrderBy(['name' => 'DESC'])]
    private Collection $children;

    /**
     * The collection a composite target fetch-joins, whose own mapped-by to-one
     * is NOT NULL — so this is the one-to-many a dissociation is refused over.
     *
     * @var Collection<int, CircuitStop>
     */
    #[ORM\OneToMany(targetEntity: CircuitStop::class, mappedBy: 'circuit')]
    private Collection $stops;

    /**
     * A many-to-many owned by a record of several fields whose target is keyed
     * by several fields too, so both ends of the join table are wide.
     *
     * @var Collection<int, Shortcut>
     */
    #[ORM\ManyToMany(targetEntity: Shortcut::class)]
    #[ORM\JoinTable(name: 'circuit_shortcut')]
    #[ORM\JoinColumn(name: 'circuit_region', referencedColumnName: 'region')]
    #[ORM\JoinColumn(name: 'circuit_slot', referencedColumnName: 'slot')]
    #[ORM\InverseJoinColumn(name: 'shortcut_host', referencedColumnName: 'host')]
    #[ORM\InverseJoinColumn(name: 'shortcut_path', referencedColumnName: 'path')]
    private Collection $shortcuts;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'owner_region', referencedColumnName: 'region')]
    #[ORM\JoinColumn(name: 'owner_slot', referencedColumnName: 'slot')]
    private ?Circuit $owner = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'related_region', referencedColumnName: 'region')]
    #[ORM\JoinColumn(name: 'related_slot', referencedColumnName: 'slot')]
    private ?Circuit $related = null;

    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private string $region = '';

    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private string $slot = '';

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column]
    private int $position = 0;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->stops = new ArrayCollection();
        $this->shortcuts = new ArrayCollection();
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function setRegion(string $region): void
    {
        $this->region = $region;
    }

    public function getSlot(): string
    {
        return $this->slot;
    }

    public function setSlot(string $slot): void
    {
        $this->slot = $slot;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getOwner(): ?self
    {
        return $this->owner;
    }

    public function setOwner(?self $owner): void
    {
        $this->owner = $owner;
    }

    public function getRelated(): ?self
    {
        return $this->related;
    }

    public function setRelated(?self $related): void
    {
        $this->related = $related;
    }

    /**
     * @return Collection<int, Circuit>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): void
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
        }

        $child->setOwner($this);
    }

    public function removeChild(self $child): void
    {
        $this->children->removeElement($child);

        if ($child->getOwner() === $this) {
            $child->setOwner(null);
        }
    }

    /**
     * @return Collection<int, CircuitStop>
     */
    public function getStops(): Collection
    {
        return $this->stops;
    }

    /**
     * @return Collection<int, Shortcut>
     */
    public function getShortcuts(): Collection
    {
        return $this->shortcuts;
    }

    public function addShortcut(Shortcut $shortcut): void
    {
        if (!$this->shortcuts->contains($shortcut)) {
            $this->shortcuts->add($shortcut);
        }
    }

    public function removeShortcut(Shortcut $shortcut): void
    {
        $this->shortcuts->removeElement($shortcut);
    }
}
