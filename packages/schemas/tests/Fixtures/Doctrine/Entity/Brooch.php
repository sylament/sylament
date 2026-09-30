<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An association onto a target that prints blank, beside an integer half.
 */
#[ORM\Entity]
class Brooch
{
    #[ORM\Id]
    #[ORM\ManyToOne]
    private ?Medallion $medallion = null;

    #[ORM\Id]
    #[ORM\Column]
    private int $slot = 0;

    public function getMedallion(): ?Medallion
    {
        return $this->medallion;
    }

    public function setMedallion(?Medallion $medallion): void
    {
        $this->medallion = $medallion;
    }

    public function getSlot(): int
    {
        return $this->slot;
    }

    public function setSlot(int $slot): void
    {
        $this->slot = $slot;
    }
}
