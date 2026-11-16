<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by one assigned integer, null until it is assigned: the root of a
 * chained derived identity ({@see CharterSeal}, {@see CharterSealClause}) and the
 * middle of {@see CharterBerth}'s key.
 */
#[ORM\Entity]
class Charter
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        public ?int $id,
    ) {
    }
}
