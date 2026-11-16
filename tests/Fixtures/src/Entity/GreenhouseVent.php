<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An owner whose identity is derived from a {@see Greenhouse}, so its key is that uuid,
 * with a many-to-many and a one-to-many.
 */
#[ORM\Entity]
class GreenhouseVent
{
    /**
     * @var Collection<int, Plant>
     */
    #[ORM\ManyToMany(targetEntity: Plant::class)]
    #[ORM\JoinTable(name: 'greenhouse_vent_plant')]
    #[ORM\JoinColumn(name: 'vent', referencedColumnName: 'greenhouse_id')]
    #[ORM\InverseJoinColumn(name: 'plant', referencedColumnName: 'id')]
    public Collection $plants;

    /**
     * @var Collection<int, Seedling>
     */
    #[ORM\OneToMany(targetEntity: Seedling::class, mappedBy: 'vent')]
    public Collection $seedlings;

    public function __construct(
        #[ORM\Id]
        #[ORM\OneToOne]
        public Greenhouse $greenhouse,
    ) {
        $this->plants = new ArrayCollection();
        $this->seedlings = new ArrayCollection();
    }
}
