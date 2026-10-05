<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A child either hex-keyed record can collect, so a count and a fetch-joined
 * collection have something to read.
 */
#[ORM\Entity]
class HexDot
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\ManyToOne(targetEntity: HexSwatch::class, inversedBy: 'dots')]
        #[ORM\JoinColumn(name: 'swatch_code', referencedColumnName: 'code')]
        private ?HexSwatch $swatch = null,
        #[ORM\ManyToOne(targetEntity: HexTile::class, inversedBy: 'dots')]
        #[ORM\JoinColumn(name: 'tile_code', referencedColumnName: 'code')]
        #[ORM\JoinColumn(name: 'tile_zone', referencedColumnName: 'zone')]
        private ?HexTile $tile = null
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSwatch(): ?HexSwatch
    {
        return $this->swatch;
    }

    public function getTile(): ?HexTile
    {
        return $this->tile;
    }
}
