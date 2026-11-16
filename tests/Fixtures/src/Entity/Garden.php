<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An owner keyed by one generated integer, with a collection of every shape a
 * relationship lists and writes: the owning side of a many-to-many whose adder does not
 * call its counterpart back, so the side a write lands on stays visible; a one-to-many of
 * ordinary children; and one each whose children cannot be dissociated, for a different
 * reason apiece: they are orphan-removed, the owner is half their identifier, their join
 * column is not nullable, or their property does not accept null.
 */
#[ORM\Entity]
class Garden
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\ManyToOne]
    public ?Product $supplier = null;

    /**
     * @var Collection<int, Plant>
     */
    #[ORM\ManyToMany(targetEntity: Plant::class, inversedBy: 'gardens')]
    public Collection $plants;

    /**
     * @var Collection<int, Seedling>
     */
    #[ORM\OneToMany(targetEntity: Seedling::class, mappedBy: 'garden')]
    public Collection $seedlings;

    /**
     * @var Collection<int, GardenPlot>
     */
    #[ORM\OneToMany(targetEntity: GardenPlot::class, mappedBy: 'garden', orphanRemoval: true)]
    public Collection $plots;

    /**
     * @var Collection<int, GardenPatch>
     */
    #[ORM\OneToMany(targetEntity: GardenPatch::class, mappedBy: 'garden')]
    public Collection $patches;

    /**
     * @var Collection<int, GardenRow>
     */
    #[ORM\OneToMany(targetEntity: GardenRow::class, mappedBy: 'garden')]
    public Collection $rows;

    /**
     * @var Collection<int, GardenLabel>
     */
    #[ORM\OneToMany(targetEntity: GardenLabel::class, mappedBy: 'garden')]
    public Collection $labels;

    public function __construct(
        #[ORM\Column]
        public string $name = '',
    ) {
        $this->plants = new ArrayCollection();
        $this->seedlings = new ArrayCollection();
        $this->plots = new ArrayCollection();
        $this->patches = new ArrayCollection();
        $this->rows = new ArrayCollection();
        $this->labels = new ArrayCollection();
    }

    public function addPlant(Plant $plant): void
    {
        $this->plants->add($plant);
    }

    public function removePlant(Plant $plant): void
    {
        $this->plants->removeElement($plant);
    }
}
