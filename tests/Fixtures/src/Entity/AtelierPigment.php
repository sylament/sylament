<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The inverse side of an {@see Atelier}'s many-to-many pigments, and half of an
 * {@see AtelierStock}'s key.
 */
#[ORM\Entity]
class AtelierPigment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, Atelier>
     */
    #[ORM\ManyToMany(targetEntity: Atelier::class, mappedBy: 'pigments')]
    public Collection $ateliers;

    public function __construct(
        #[ORM\Column]
        public string $hue = '',
    ) {
        $this->ateliers = new ArrayCollection();
    }
}
