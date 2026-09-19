<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A backed enum inside a composite identifier: the record reads it back as its
 * backing value, an aliased scalar row as the enum instance.
 */
#[ORM\Entity]
class Placement
{
    #[ORM\Id]
    #[ORM\Column(length: 16, enumType: Zone::class)]
    private Zone $zone = Zone::Header;

    #[ORM\Id]
    #[ORM\Column]
    private int $slot = 0;

    #[ORM\Column]
    private string $label = '';

    public function getZone(): Zone
    {
        return $this->zone;
    }

    public function setZone(Zone $zone): void
    {
        $this->zone = $zone;
    }

    public function getSlot(): int
    {
        return $this->slot;
    }

    public function setSlot(int $slot): void
    {
        $this->slot = $slot;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}
