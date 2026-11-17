<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The embeddable a {@see StowedCrate} is labelled with: a port, and the place on board
 * as an embeddable nested inside it.
 */
#[ORM\Embeddable]
class StowageLabel
{
    #[ORM\Embedded]
    public StowagePlace $place;

    public function __construct(
        #[ORM\Column(length: 32)]
        public string $port = '',
        string $deck = '',
    ) {
        $this->place = new StowagePlace($deck);
    }
}
