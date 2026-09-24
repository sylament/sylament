<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A generated-id owner whose every association cascades persist, so a flush of
 * the binder inserts whatever new records it holds: children keyed by the
 * binder plus a code, children keyed by a code the class fills itself, two join
 * entities keyed by the binder and a tag in either field order, children keyed
 * by a code their column stores lower-cased, and a to-one keyed by a code
 * alone.
 */
#[ORM\Entity]
class Binder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $title = '';

    /**
     * @var Collection<int, Sheet>
     */
    #[ORM\OneToMany(targetEntity: Sheet::class, mappedBy: 'binder', cascade: ['persist'])]
    private Collection $sheets;

    /**
     * @var Collection<int, Slate>
     */
    #[ORM\OneToMany(targetEntity: Slate::class, mappedBy: 'binder', cascade: ['persist'])]
    private Collection $slates;

    /**
     * @var Collection<int, Ribbon>
     */
    #[ORM\OneToMany(targetEntity: Ribbon::class, mappedBy: 'binder', cascade: ['persist'])]
    private Collection $ribbons;

    /**
     * @var Collection<int, Pennant>
     */
    #[ORM\OneToMany(targetEntity: Pennant::class, mappedBy: 'binder', cascade: ['persist'])]
    private Collection $pennants;

    /**
     * @var Collection<int, Divider>
     */
    #[ORM\OneToMany(targetEntity: Divider::class, mappedBy: 'binder', cascade: ['persist'])]
    private Collection $dividers;

    #[ORM\OneToOne(targetEntity: Spine::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'spine_code', referencedColumnName: 'code')]
    private ?Spine $spine = null;

    public function __construct()
    {
        $this->sheets = new ArrayCollection();
        $this->slates = new ArrayCollection();
        $this->ribbons = new ArrayCollection();
        $this->pennants = new ArrayCollection();
        $this->dividers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return Collection<int, Sheet>
     */
    public function getSheets(): Collection
    {
        return $this->sheets;
    }

    public function addSheet(Sheet $sheet): void
    {
        if (! $this->sheets->contains($sheet)) {
            $this->sheets->add($sheet);
            $sheet->setBinder($this);
        }
    }

    /**
     * @return Collection<int, Slate>
     */
    public function getSlates(): Collection
    {
        return $this->slates;
    }

    public function addSlate(Slate $slate): void
    {
        if (! $this->slates->contains($slate)) {
            $this->slates->add($slate);
            $slate->setBinder($this);
        }
    }

    /**
     * @return Collection<int, Ribbon>
     */
    public function getRibbons(): Collection
    {
        return $this->ribbons;
    }

    public function addRibbon(Ribbon $ribbon): void
    {
        if (! $this->ribbons->contains($ribbon)) {
            $this->ribbons->add($ribbon);
            $ribbon->setBinder($this);
        }
    }

    /**
     * @return Collection<int, Pennant>
     */
    public function getPennants(): Collection
    {
        return $this->pennants;
    }

    public function addPennant(Pennant $pennant): void
    {
        if (! $this->pennants->contains($pennant)) {
            $this->pennants->add($pennant);
            $pennant->setBinder($this);
        }
    }

    /**
     * @return Collection<int, Divider>
     */
    public function getDividers(): Collection
    {
        return $this->dividers;
    }

    public function addDivider(Divider $divider): void
    {
        if (! $this->dividers->contains($divider)) {
            $this->dividers->add($divider);
            $divider->setBinder($this);
        }
    }

    public function getSpine(): ?Spine
    {
        return $this->spine;
    }

    public function setSpine(?Spine $spine): void
    {
        $this->spine = $spine;
    }
}
