<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Tests\Fixtures\Schemas\Doctrine\HexCodeType;

/**
 * A record keyed by an integer column it holds as hex, through a reader that is not idempotent.
 */
#[ORM\Entity]
class HexCodedSwatch
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: HexCodeType::NAME)]
        public string $code,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
