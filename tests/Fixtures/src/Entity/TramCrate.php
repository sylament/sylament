<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A child of {@see TramDepot::$crates} keyed by two integers, the {@see OrderLine} flavour,
 * with two text fields, so a search can name the one that is not the title.
 */
#[ORM\Entity]
class TramCrate
{
    #[ORM\ManyToOne(inversedBy: 'crates')]
    public ?TramDepot $depot = null;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        public int $bay,
        #[ORM\Id]
        #[ORM\Column]
        public int $slot,
        #[ORM\Column]
        public string $label = '',
        #[ORM\Column]
        public string $contents = '',
    ) {
    }
}
