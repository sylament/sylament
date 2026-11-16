<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by its association alone onto a string-keyed {@see StageVenue},
 * which a new record may leave unset.
 */
#[ORM\Entity]
class VenueKeyedPlaybill
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(referencedColumnName: 'handle')]
        public ?StageVenue $venue = null,
    ) {
    }
}
