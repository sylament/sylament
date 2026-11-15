<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by an association plus an integer, the second composite flavour
 * beside {@see OrderLine}; the association cascades a persist, so a seed names
 * only the lines.
 */
#[ORM\Entity]
class InvoiceLine
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(cascade: ['persist'])]
        public Invoice $invoice,
        #[ORM\Id]
        #[ORM\Column]
        public int $line,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
