<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by one string only its constructor sets, which no setter can change.
 */
#[ORM\Entity]
class ConstructorCodedBadge
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        public readonly string $code,
        #[ORM\Column]
        public string $label = '',
    ) {
    }
}
