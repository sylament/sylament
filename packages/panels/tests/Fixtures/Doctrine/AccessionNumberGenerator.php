<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Accession;

/**
 * Numbers every {@see Accession} `A-1`, `A-2`, … in persist order. A test that
 * seeds accessions resets {@see $next} first.
 */
final class AccessionNumberGenerator extends AbstractIdGenerator
{
    public static int $next = 1;

    public function generateId(EntityManagerInterface $em, ?object $entity): string
    {
        return 'A-' . self::$next++;
    }
}
