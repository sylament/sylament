<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An owner keyed by one generated integer, held in the second-level cache with its
 * collection of {@see CachedLantern}s, so a write that bypasses the unit of work shows
 * whether it evicted what it changed.
 */
#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class LanternShelf
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, CachedLantern>
     */
    #[ORM\OneToMany(targetEntity: CachedLantern::class, mappedBy: 'shelf')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    public Collection $lanterns;

    public function __construct(
        #[ORM\Column]
        public string $name = '',
    ) {
        $this->lanterns = new ArrayCollection();
    }
}
