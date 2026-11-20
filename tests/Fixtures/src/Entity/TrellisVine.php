<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An item of {@see Trellis}'s vines with a generated key.
 */
#[ORM\Entity]
class TrellisVine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\ManyToOne(inversedBy: 'vines')]
    public ?Trellis $trellis = null;

    public function __construct(
        #[ORM\Column(length: 40)]
        public string $name = '',
    ) {
    }
}
