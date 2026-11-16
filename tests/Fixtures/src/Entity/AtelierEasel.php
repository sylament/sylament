<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An item of an {@see Atelier}'s ordered, orphan-removing easels, whose join column
 * refuses null.
 */
#[ORM\Entity]
class AtelierEasel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'easels')]
        #[ORM\JoinColumn(nullable: false)]
        public Atelier $atelier,
        #[ORM\Column]
        public int $position = 0,
    ) {
    }
}
