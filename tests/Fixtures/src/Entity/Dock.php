<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An owner with a generated key holding two one-to-many collections of one target, the
 * {@see Voyage}s arriving at it and those departing from it, so no single association names
 * its voyages.
 */
#[ORM\Entity]
class Dock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, Voyage>
     */
    #[ORM\OneToMany(targetEntity: Voyage::class, mappedBy: 'arrivalDock')]
    public Collection $arrivals;

    /**
     * @var Collection<int, Voyage>
     */
    #[ORM\OneToMany(targetEntity: Voyage::class, mappedBy: 'departureDock')]
    public Collection $departures;

    public function __construct(
        #[ORM\Column(length: 40)]
        public string $name = '',
    ) {
        $this->arrivals = new ArrayCollection();
        $this->departures = new ArrayCollection();
    }
}
