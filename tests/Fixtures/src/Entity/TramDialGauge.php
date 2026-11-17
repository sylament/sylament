<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The single-table leaf of {@see TramGauge} that a {@see TramDepot}'s dials list.
 */
#[ORM\Entity]
class TramDialGauge extends TramGauge
{
    #[ORM\ManyToOne(inversedBy: 'dials')]
    public ?TramDepot $depot = null;
}
