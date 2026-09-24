<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The same join entity as {@see Booking} with the band declared first, so the
 * owning festival is the second identifier field.
 */
#[ORM\Entity]
class Headliner
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Band::class)]
    private ?Band $band = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Festival::class, inversedBy: 'headliners')]
    private ?Festival $festival = null;

    #[ORM\Column(length: 20)]
    private string $slot = '';

    public function getBand(): ?Band
    {
        return $this->band;
    }

    public function setBand(?Band $band): void
    {
        $this->band = $band;
    }

    public function getFestival(): ?Festival
    {
        return $this->festival;
    }

    public function setFestival(?Festival $festival): void
    {
        $this->festival = $festival;
    }

    public function getSlot(): string
    {
        return $this->slot;
    }

    public function setSlot(string $slot): void
    {
        $this->slot = $slot;
    }
}
