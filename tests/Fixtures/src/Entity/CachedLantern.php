<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The child of a {@see LanternShelf} and of a {@see LanternStand}, held in the second-level
 * cache like both.
 */
#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class CachedLantern
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\ManyToOne(inversedBy: 'lanterns')]
    public ?LanternShelf $shelf = null;

    #[ORM\ManyToOne(inversedBy: 'lanterns')]
    #[ORM\JoinColumn(name: 'stand_aisle', referencedColumnName: 'aisle')]
    #[ORM\JoinColumn(name: 'stand_bay', referencedColumnName: 'bay')]
    public ?LanternStand $stand = null;

    public function __construct(
        #[ORM\Column]
        public string $name = '',
    ) {
    }
}
