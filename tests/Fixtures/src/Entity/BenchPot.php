<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by two strings the application assigns, held in a {@see NurseryBench}.
 */
#[ORM\Entity]
class BenchPot
{
    #[ORM\ManyToOne(inversedBy: 'pots')]
    public ?NurseryBench $bench = null;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 40)]
        public string $bay = '',
        #[ORM\Id]
        #[ORM\Column(length: 40)]
        public string $slot = '',
        #[ORM\Column(length: 40)]
        public string $plant = '',
    ) {
    }
}
