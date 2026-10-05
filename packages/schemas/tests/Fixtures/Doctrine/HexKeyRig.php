<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\ORM\EntityManager;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\HexSwatch;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\HexTile;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\MarkedSwatch;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\MarkedTile;

/**
 * Records keyed through the two scalar types a value read twice is not the same
 * value for: {@see HexCodeType}, whose reader misreads its own output, and
 * {@see MarkedCodeType}, whose reader refuses it.
 */
final class HexKeyRig
{
    public static function create(): EntityManager
    {
        $entityManager = EntityManagerFactory::create();

        EntityManagerFactory::seed(
            $entityManager,
            new HexSwatch('ff', 'Ivory'),
            new HexSwatch('1a', 'Umber'),
            new HexTile('ff', 'north', 'Ivory north'),
            new HexTile('ff', 'south', 'Ivory south'),
            new HexTile('1a', 'north', 'Umber north'),
            new MarkedSwatch('alpha', 'Alpha'),
            new MarkedSwatch('beta', 'Beta'),
            new MarkedTile('alpha', 'north', 'Alpha north'),
            new MarkedTile('beta', 'south', 'Beta south'),
        );

        return $entityManager;
    }
}
