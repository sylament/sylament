<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by a string-backed enum beside an integer.
 */
#[ORM\Entity]
class ShelfPlacement
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 16)]
        public ShelfZone $zone,
        #[ORM\Id]
        #[ORM\Column]
        public int $slot,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
