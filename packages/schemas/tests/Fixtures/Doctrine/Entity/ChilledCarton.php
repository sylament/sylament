<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A subclass of {@see Carton} with a table of its own, keyed through its root's.
 */
#[ORM\Entity]
class ChilledCarton extends Carton
{
    #[ORM\Column]
    private int $celsius = 4;

    public function getCelsius(): int
    {
        return $this->celsius;
    }

    public function setCelsius(int $celsius): void
    {
        $this->celsius = $celsius;
    }
}
