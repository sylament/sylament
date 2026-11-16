<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single derived identity: keyed by its charter alone.
 */
#[ORM\Entity]
class CharterSeal
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne]
        public Charter $charter,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
