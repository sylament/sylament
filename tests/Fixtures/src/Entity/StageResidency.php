<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A derived identity onto a string-keyed parent, beside a string.
 */
#[ORM\Entity]
class StageResidency
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(cascade: ['persist'])]
        #[ORM\JoinColumn(referencedColumnName: 'handle')]
        public StageVenue $venue,
        #[ORM\Id]
        #[ORM\Column(length: 64)]
        public string $season,
        #[ORM\Column]
        public string $note = '',
    ) {
    }
}
