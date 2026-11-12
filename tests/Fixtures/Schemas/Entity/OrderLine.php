<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by two integers. `order` is a reserved word on every platform, so
 * the key columns are named apart from their properties.
 */
#[ORM\Entity]
class OrderLine
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(name: 'order_number')]
        public int $order,
        #[ORM\Id]
        #[ORM\Column(name: 'line_number')]
        public int $line,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
