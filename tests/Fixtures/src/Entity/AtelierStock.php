<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A join record keyed by two associations and nothing else: an {@see Atelier} and the
 * {@see AtelierPigment} it stocks.
 */
#[ORM\Entity]
class AtelierStock
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(inversedBy: 'stocks')]
        public Atelier $atelier,
        #[ORM\Id]
        #[ORM\ManyToOne]
        public AtelierPigment $pigment,
        #[ORM\Column]
        public int $tubes = 0,
    ) {
    }
}
