<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The embeddable a {@see StowageLabel} nests, mapped on a {@see StowedCrate} as
 * `label.place.deck`.
 */
#[ORM\Embeddable]
class StowagePlace
{
    public function __construct(
        #[ORM\Column(length: 32)]
        public string $deck = '',
    ) {
    }
}
