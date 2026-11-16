<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The target of every owner's many-to-many, holding the inverse side of
 * {@see Garden::$plants}; its adder does not call the garden back.
 */
#[ORM\Entity]
class Plant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, Garden>
     */
    #[ORM\ManyToMany(targetEntity: Garden::class, mappedBy: 'plants')]
    public Collection $gardens;

    public function __construct(
        #[ORM\Column]
        public string $name = '',
    ) {
        $this->gardens = new ArrayCollection();
    }

    public function addGarden(Garden $garden): void
    {
        $this->gardens->add($garden);
    }

    public function removeGarden(Garden $garden): void
    {
        $this->gardens->removeElement($garden);
    }
}
