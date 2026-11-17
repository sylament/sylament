<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SortDirection;

/**
 * An owner with a generated key and a supplier of its own, holding three orphan-removing
 * collections a relationship repeater edits, each with an adder and a remover: the
 * {@see LarderJar}s it orders by position, the {@see LarderCrock}s keyed by the larder and
 * a shelf, and the {@see LarderBin}s keyed by a code the application assigns.
 */
#[ORM\Entity]
class Larder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\ManyToOne]
    public ?Product $supplier = null;

    /**
     * @var Collection<int, LarderJar>
     */
    #[ORM\OneToMany(targetEntity: LarderJar::class, mappedBy: 'larder', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => SortDirection::Ascending])]
    public Collection $jars;

    /**
     * @var Collection<int, LarderCrock>
     */
    #[ORM\OneToMany(targetEntity: LarderCrock::class, mappedBy: 'larder', cascade: ['persist'], orphanRemoval: true)]
    public Collection $crocks;

    /**
     * @var Collection<int, LarderBin>
     */
    #[ORM\OneToMany(targetEntity: LarderBin::class, mappedBy: 'larder', cascade: ['persist'], orphanRemoval: true)]
    public Collection $bins;

    public function __construct(
        #[ORM\Column(length: 40)]
        public string $name = '',
    ) {
        $this->jars = new ArrayCollection();
        $this->crocks = new ArrayCollection();
        $this->bins = new ArrayCollection();
    }

    public function addJar(LarderJar $jar): void
    {
        if (!$this->jars->contains($jar)) {
            $this->jars->add($jar);
            $jar->larder = $this;
        }
    }

    public function removeJar(LarderJar $jar): void
    {
        if ($this->jars->removeElement($jar)) {
            $jar->larder = null;
        }
    }

    public function addCrock(LarderCrock $crock): void
    {
        if (!$this->crocks->contains($crock)) {
            $this->crocks->add($crock);
            $crock->larder = $this;
        }
    }

    public function removeCrock(LarderCrock $crock): void
    {
        $this->crocks->removeElement($crock);
    }

    public function addBin(LarderBin $bin): void
    {
        if (!$this->bins->contains($bin)) {
            $this->bins->add($bin);
            $bin->larder = $this;
        }
    }

    public function removeBin(LarderBin $bin): void
    {
        if ($this->bins->removeElement($bin)) {
            $bin->larder = null;
        }
    }
}
