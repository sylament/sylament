<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The to-one side of a {@see Convoy}'s wagons.
 */
#[ORM\Entity]
class ConvoyWagon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'wagons')]
        public Convoy $convoy,
        #[ORM\Column]
        public string $cargo = '',
    ) {
    }
}
