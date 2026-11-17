<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The joined leaf of {@see TramAppliance} that a {@see TramDepot}'s toasters list.
 */
#[ORM\Entity]
class TramToaster extends TramAppliance
{
    #[ORM\Column]
    public int $slots = 2;
}
