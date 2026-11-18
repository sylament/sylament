<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The embeddable a {@see ShelvedPreserve} is placed through, mapped as `spot.position`.
 */
#[ORM\Embeddable]
class PreserveSpot
{
    public function __construct(
        #[ORM\Column]
        public int $position = 0,
    ) {
    }
}
