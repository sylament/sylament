<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The record a {@see ClaspedLocket} derives its identity from, keyed by one string the
 * application assigns, blank until written.
 */
#[ORM\Entity]
class LocketClasp
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        public string $code = '',
    ) {
    }
}
