<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A record with a collection-valued side: the {@see ConvoyWagon}s that point back at it.
 */
#[ORM\Entity]
class Convoy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, ConvoyWagon>
     */
    #[ORM\OneToMany(targetEntity: ConvoyWagon::class, mappedBy: 'convoy')]
    public Collection $wagons;

    public function __construct(
        #[ORM\Column]
        public string $label = '',
    ) {
        $this->wagons = new ArrayCollection();
    }
}
