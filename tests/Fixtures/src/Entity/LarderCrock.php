<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An item of {@see Larder::$crocks} keyed by its larder plus a shelf the application
 * assigns, blank until written, constructible without arguments.
 */
#[ORM\Entity]
class LarderCrock
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(inversedBy: 'crocks')]
        public ?Larder $larder = null,
        #[ORM\Id]
        #[ORM\Column(length: 40)]
        public string $shelf = '',
        #[ORM\Column(length: 40)]
        public string $label = '',
    ) {
    }
}
