<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by three fields with an association in the middle.
 */
#[ORM\Entity]
class CharterBerth
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        public string $harbour,
        #[ORM\Id]
        #[ORM\ManyToOne]
        public Charter $charter,
        #[ORM\Id]
        #[ORM\Column]
        public int $berth,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
