<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An owner keyed by a {@see Garden} and a number, the {@see InvoiceLine} flavour, with a
 * many-to-many and a one-to-many.
 */
#[ORM\Entity]
class Allotment
{
    /**
     * @var Collection<int, Plant>
     */
    #[ORM\ManyToMany(targetEntity: Plant::class)]
    #[ORM\JoinTable(name: 'allotment_plant')]
    #[ORM\JoinColumn(name: 'allotment_garden', referencedColumnName: 'garden_id')]
    #[ORM\JoinColumn(name: 'allotment_number', referencedColumnName: 'number')]
    #[ORM\InverseJoinColumn(name: 'plant', referencedColumnName: 'id')]
    public Collection $plants;

    /**
     * @var Collection<int, Seedling>
     */
    #[ORM\OneToMany(targetEntity: Seedling::class, mappedBy: 'allotment')]
    public Collection $seedlings;

    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(cascade: ['persist'])]
        public Garden $garden,
        #[ORM\Id]
        #[ORM\Column]
        public int $number,
    ) {
        $this->plants = new ArrayCollection();
        $this->seedlings = new ArrayCollection();
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
