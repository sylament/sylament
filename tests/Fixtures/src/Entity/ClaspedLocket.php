<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record whose identifier is its {@see LocketClasp}, which its no-argument constructor
 * builds with a blank code, so its whole key flattens to `''` until the clasp is keyed.
 */
#[ORM\Entity]
class ClaspedLocket
{
    #[ORM\Id]
    #[ORM\OneToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(referencedColumnName: 'code')]
    public LocketClasp $clasp;

    #[ORM\Column(length: 32)]
    public string $label = '';

    public function __construct()
    {
        $this->clasp = new LocketClasp();
    }
}
