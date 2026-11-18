<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record with a generated key and a label, whose position sits in an embeddable.
 */
#[ORM\Entity]
class ShelvedPreserve
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\Embedded]
    public PreserveSpot $spot;

    public function __construct(
        #[ORM\Column(length: 40)]
        public string $label = '',
        int $position = 0,
    ) {
        $this->spot = new PreserveSpot($position);
    }
}
