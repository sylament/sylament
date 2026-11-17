<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by two scalars, one of them called `id`, so `id` alone names no record.
 */
#[ORM\Entity]
class LocalisedLeaflet
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        public int $id,
        #[ORM\Id]
        #[ORM\Column(length: 8)]
        public string $locale,
    ) {
    }
}
