<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by an int-backed enum beside a string.
 */
#[ORM\Entity]
class RackSlot
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        public RackTier $tier,
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        public string $bay,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
