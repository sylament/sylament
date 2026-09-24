<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A join entity whose identifier is two associations, the owning festival
 * first.
 */
#[ORM\Entity]
class Booking
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Festival::class, inversedBy: 'bookings')]
    private ?Festival $festival = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Band::class)]
    private ?Band $band = null;

    #[ORM\Column(length: 20)]
    private string $slot = '';

    public function getFestival(): ?Festival
    {
        return $this->festival;
    }

    public function setFestival(?Festival $festival): void
    {
        $this->festival = $festival;
    }

    public function getBand(): ?Band
    {
        return $this->band;
    }

    public function setBand(?Band $band): void
    {
        $this->band = $band;
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
