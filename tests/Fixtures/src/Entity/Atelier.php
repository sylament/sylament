<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SortDirection;
use Stringable;

/**
 * A record holding one association of every shape a mapping states: an ordered
 * collection whose items it owns, a plain collection, the inverse side of a one-to-one,
 * the owning side of a many-to-many, and two collections it cascades a persist to whose
 * members it is half the key of. It prints its name, which is blank until one is given.
 */
#[ORM\Entity]
class Atelier implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, AtelierEasel>
     */
    #[ORM\OneToMany(targetEntity: AtelierEasel::class, mappedBy: 'atelier', orphanRemoval: true)]
    #[ORM\OrderBy(['position' => SortDirection::Ascending])]
    public Collection $easels;

    /**
     * @var Collection<int, AtelierApprentice>
     */
    #[ORM\OneToMany(targetEntity: AtelierApprentice::class, mappedBy: 'atelier')]
    public Collection $apprentices;

    #[ORM\OneToOne(targetEntity: AtelierLease::class, mappedBy: 'atelier')]
    public ?AtelierLease $lease = null;

    /**
     * @var Collection<int, AtelierPigment>
     */
    #[ORM\ManyToMany(targetEntity: AtelierPigment::class, inversedBy: 'ateliers')]
    public Collection $pigments;

    /**
     * @var Collection<int, AtelierPlaque>
     */
    #[ORM\OneToMany(targetEntity: AtelierPlaque::class, mappedBy: 'atelier', cascade: ['persist'])]
    public Collection $plaques;

    /**
     * @var Collection<int, AtelierStock>
     */
    #[ORM\OneToMany(targetEntity: AtelierStock::class, mappedBy: 'atelier', cascade: ['persist'])]
    public Collection $stocks;

    public function __construct(
        #[ORM\Column]
        public string $name = '',
    ) {
        $this->easels = new ArrayCollection();
        $this->apprentices = new ArrayCollection();
        $this->pigments = new ArrayCollection();
        $this->plaques = new ArrayCollection();
        $this->stocks = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
