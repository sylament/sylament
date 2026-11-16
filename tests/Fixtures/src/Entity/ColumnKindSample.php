<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One column per kind of mapped type a search tells apart: text, a guid, a boolean and a date and time.
 */
#[ORM\Entity]
class ColumnKindSample
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    public function __construct(
        #[ORM\Column(type: Types::TEXT)]
        public string $body = '',
        #[ORM\Column(type: Types::GUID)]
        public string $token = '00000000-0000-0000-0000-000000000000',
        #[ORM\Column]
        public bool $flagged = false,
        #[ORM\Column]
        public DateTimeImmutable $loggedAt = new DateTimeImmutable('2001-01-01 00:00:00'),
    ) {
    }
}
