<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by three scalar fields, the last null until it is assigned.
 */
#[ORM\Entity]
class AisleSeat
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        public string $hall,
        #[ORM\Id]
        #[ORM\Column]
        public int $aisle,
        #[ORM\Id]
        #[ORM\Column]
        public ?int $seat = null,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
