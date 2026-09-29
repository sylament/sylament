<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A composite identifier with an association in the middle of its three fields.
 */
#[ORM\Entity]
class Mooring
{
    #[ORM\Id]
    #[ORM\Column(length: 16)]
    private string $lane = '';

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Dock::class)]
    private ?Dock $dock = null;

    #[ORM\Id]
    #[ORM\Column]
    private int $seq = 0;

    public function getLane(): string
    {
        return $this->lane;
    }

    public function setLane(string $lane): void
    {
        $this->lane = $lane;
    }

    public function getDock(): ?Dock
    {
        return $this->dock;
    }

    public function setDock(?Dock $dock): void
    {
        $this->dock = $dock;
    }

    public function getSeq(): int
    {
        return $this->seq;
    }

    public function setSeq(int $seq): void
    {
        $this->seq = $seq;
    }
}
