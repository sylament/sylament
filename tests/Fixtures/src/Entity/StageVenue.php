<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The string-keyed parent half of a {@see StageResidency}'s key.
 */
#[ORM\Entity]
class StageVenue
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 64)]
        public string $handle,
    ) {
    }
}
