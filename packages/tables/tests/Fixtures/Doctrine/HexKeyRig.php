<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\HexDot;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\HexOwner;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\HexSwatch;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\HexTile;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\MarkedTag;

/**
 * Records keyed through the two scalar types a value read twice is not the same
 * value for. Ada (owner 1) already holds and keeps the swatch `c` and the tile
 * `1a~north`, so every candidate query has something to exclude; the swatch `ff`
 * collects two dots and `1a` one, the tile `ff~north` two and `ff~south` one.
 */
final class HexKeyRig
{
    public static function seed(EntityManagerInterface $entityManager): void
    {
        $ada = new HexOwner(1, 'Ada');
        $bob = new HexOwner(2, 'Bob');

        $ivory = new HexSwatch('ff', 'Ivory');
        $umber = new HexSwatch('1a', 'Umber');
        $coal = new HexSwatch('c', 'Coal');

        $ivoryNorth = new HexTile('ff', 'north', 'Ivory north');
        $ivorySouth = new HexTile('ff', 'south', 'Ivory south');
        $umberNorth = new HexTile('1a', 'north', 'Umber north');

        $ada->addSwatch($coal);
        $ada->addTile($umberNorth);

        $coal->setKeeper($ada);
        $umberNorth->setKeeper($ada);

        foreach ([
            $ada,
            $bob,
            $ivory,
            $umber,
            $coal,
            $ivoryNorth,
            $ivorySouth,
            $umberNorth,
            new HexDot(1, swatch: $ivory, tile: $ivoryNorth),
            new HexDot(2, swatch: $ivory, tile: $ivoryNorth),
            new HexDot(3, swatch: $umber, tile: $ivorySouth),
            new MarkedTag('alpha', 'Alpha'),
            new MarkedTag('beta', 'Beta'),
        ] as $entity) {
            $entityManager->persist($entity);
        }

        $entityManager->flush();
    }
}
