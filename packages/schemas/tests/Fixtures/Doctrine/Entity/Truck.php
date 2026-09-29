<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A subclass of {@see Vehicle} with a table of its own, keyed through its root's composite identifier.
 */
#[ORM\Entity]
class Truck extends Vehicle
{
    #[ORM\Column]
    private int $axles = 0;

    public function getAxles(): int
    {
        return $this->axles;
    }

    public function setAxles(int $axles): void
    {
        $this->axles = $axles;
    }
}
