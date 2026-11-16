<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Tests\Fixtures\Schemas\Doctrine\MarkedCodeType;

/**
 * A record keyed by a string whose strict reader refuses a value it has already read.
 */
#[ORM\Entity]
class MarkedCodeLabel
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: MarkedCodeType::NAME, length: 64)]
        public string $code,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
