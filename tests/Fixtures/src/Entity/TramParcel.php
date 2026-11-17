<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A child of {@see TramDepot::$parcels} keyed by an {@see Invoice} plus an integer, the
 * {@see InvoiceLine} flavour; the invoice cascades a persist, so a seed names only the
 * parcels.
 */
#[ORM\Entity]
class TramParcel
{
    #[ORM\ManyToOne(inversedBy: 'parcels')]
    public ?TramDepot $depot = null;

    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(cascade: ['persist'])]
        public Invoice $invoice,
        #[ORM\Id]
        #[ORM\Column]
        public int $line,
        #[ORM\Column]
        public string $label = '',
    ) {
    }
}
