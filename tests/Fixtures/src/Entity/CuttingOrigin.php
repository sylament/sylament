<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The embeddable a {@see PropagatedCutting} names its nursery through, mapped as
 * `origin.nursery`.
 */
#[ORM\Embeddable]
class CuttingOrigin
{
    public function __construct(
        #[ORM\Column]
        public string $nursery = '',
    ) {
    }
}
