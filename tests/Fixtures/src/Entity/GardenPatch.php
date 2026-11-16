<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A child of {@see Garden::$patches} keyed by its garden and a code, so the garden is half
 * its identifier.
 */
#[ORM\Entity]
class GardenPatch
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(inversedBy: 'patches')]
        public Garden $garden,
        #[ORM\Id]
        #[ORM\Column]
        public string $code,
    ) {
    }
}
