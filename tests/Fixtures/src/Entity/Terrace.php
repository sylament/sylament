<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * An owner keyed by two scalars, the {@see OrderLine} flavour, with a many-to-many and a
 * one-to-many. The estate is a `bigint`, so an estate held as a string its type would
 * never print (`'.5'`) mints a key that does not decode.
 */
#[ORM\Entity]
class Terrace
{
    /**
     * @var Collection<int, Plant>
     */
    #[ORM\ManyToMany(targetEntity: Plant::class)]
    #[ORM\JoinTable(name: 'terrace_plant')]
    #[ORM\JoinColumn(name: 'terrace_estate', referencedColumnName: 'estate')]
    #[ORM\JoinColumn(name: 'terrace_number', referencedColumnName: 'number')]
    #[ORM\InverseJoinColumn(name: 'plant', referencedColumnName: 'id')]
    public Collection $plants;

    /**
     * @var Collection<int, Seedling>
     */
    #[ORM\OneToMany(targetEntity: Seedling::class, mappedBy: 'terrace')]
    public Collection $seedlings;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: Types::BIGINT)]
        public int|string $estate,
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
