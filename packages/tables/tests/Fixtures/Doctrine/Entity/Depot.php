<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A single-identifier owner of two one-to-manys whose foreign key the target
 * class does not hold in a table of its own: a leaf of a joined hierarchy whose
 * root declares the mapped-by, and a leaf of a single-table one.
 */
#[ORM\Entity]
class Depot
{
    /**
     * @var Collection<int, Toaster>
     */
    #[ORM\OneToMany(targetEntity: Toaster::class, mappedBy: 'depot')]
    private Collection $toasters;

    /**
     * @var Collection<int, Dial>
     */
    #[ORM\OneToMany(targetEntity: Dial::class, mappedBy: 'depot')]
    private Collection $dials;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\Column]
        private string $name,
    ) {
        $this->toasters = new ArrayCollection();
        $this->dials = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<int, Toaster>
     */
    public function getToasters(): Collection
    {
        return $this->toasters;
    }

    /**
     * @return Collection<int, Dial>
     */
    public function getDials(): Collection
    {
        return $this->dials;
    }
}
