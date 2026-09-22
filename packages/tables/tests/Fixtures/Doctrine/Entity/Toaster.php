<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The joined leaf a {@see Depot} collects, whose own table holds no foreign key.
 */
#[ORM\Entity]
class Toaster extends Appliance
{
    #[ORM\Column]
    private int $slots = 2;

    public function getSlots(): int
    {
        return $this->slots;
    }
}
