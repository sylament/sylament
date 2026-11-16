<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by an ascii string beside a smallint.
 */
#[ORM\Entity]
class ShortlinkSlot
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: Types::ASCII_STRING, length: 32)]
        public string $domain,
        #[ORM\Id]
        #[ORM\Column(type: Types::SMALLINT)]
        public int $slot,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
