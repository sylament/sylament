<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\SecondaryEntity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An item of {@see Observatory::$telescopes}, mapped by the `secondary` manager only.
 */
#[ORM\Entity]
class Telescope
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    public function __construct(
        #[ORM\Column(length: 40)]
        public string $name = '',
        #[ORM\ManyToOne(inversedBy: 'telescopes')]
        public ?Observatory $observatory = null,
    ) {
    }
}
