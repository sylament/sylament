<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A jar stamped with the day it was sealed on, a column holding a date and no time.
 */
#[ORM\Entity]
class DayStampedJar
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    public function __construct(
        #[ORM\Column(length: 40)]
        public string $label,
        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        public DateTimeImmutable $sealedOn,
    ) {
    }
}
