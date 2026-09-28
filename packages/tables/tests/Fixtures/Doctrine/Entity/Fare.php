<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A scalar pair whose first half is a decimal, so two spellings of one value —
 * `1` and `1.0` — both decode through the codec and both match the same row on
 * SQLite's numeric column affinity. Reached from an artist through an owning
 * many-to-many and through an ordinary nullable one-to-many.
 */
#[ORM\Entity]
class Fare
{
    #[ORM\ManyToOne(targetEntity: Artist::class, inversedBy: 'curatedFares')]
    private ?Artist $curator = null;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
        private string $rate,
        #[ORM\Id]
        #[ORM\Column(length: 16)]
        private string $zone,
        #[ORM\Column]
        private string $label,
    ) {
    }

    public function getRate(): string
    {
        return $this->rate;
    }

    public function getZone(): string
    {
        return $this->zone;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCurator(): ?Artist
    {
        return $this->curator;
    }

    public function setCurator(?Artist $curator): void
    {
        $this->curator = $curator;
    }
}
