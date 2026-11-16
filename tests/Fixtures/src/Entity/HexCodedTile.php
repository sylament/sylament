<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A derived identity onto a hex-keyed swatch, beside a string: an association half whose
 * target's type converts.
 */
#[ORM\Entity]
class HexCodedTile
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(cascade: ['persist'])]
        #[ORM\JoinColumn(referencedColumnName: 'code')]
        public HexCodedSwatch $swatch,
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        public string $lane,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
