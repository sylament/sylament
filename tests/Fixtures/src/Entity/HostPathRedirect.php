<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by two strings, whose components carry every character a key escapes.
 */
#[ORM\Entity]
class HostPathRedirect
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        public string $host,
        #[ORM\Id]
        #[ORM\Column]
        public string $path,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
