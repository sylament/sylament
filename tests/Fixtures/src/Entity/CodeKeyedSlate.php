<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by one assigned string.
 */
#[ORM\Entity]
class CodeKeyedSlate
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 64)]
        public string $code,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
