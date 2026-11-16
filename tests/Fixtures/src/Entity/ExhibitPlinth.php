<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The composite-keyed root of a JOINED hierarchy whose leaf is {@see StatuePlinth}.
 */
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'kind', type: 'string', length: 16)]
#[ORM\DiscriminatorMap(['exhibit' => ExhibitPlinth::class, 'statue' => StatuePlinth::class])]
class ExhibitPlinth
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        public string $site,
        #[ORM\Id]
        #[ORM\Column]
        public int $bay,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
