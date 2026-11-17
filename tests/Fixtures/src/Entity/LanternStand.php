<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * {@see LanternShelf} keyed by two integers, so the foreign key onto it spans two columns.
 */
#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class LanternStand
{
    /**
     * @var Collection<int, CachedLantern>
     */
    #[ORM\OneToMany(targetEntity: CachedLantern::class, mappedBy: 'stand')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    public Collection $lanterns;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        public int $aisle,
        #[ORM\Id]
        #[ORM\Column]
        public int $bay,
    ) {
        $this->lanterns = new ArrayCollection();
    }
}
