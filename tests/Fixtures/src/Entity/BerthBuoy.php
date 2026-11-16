<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A {@see Berth}'s record keyed by a single string the application assigns.
 */
#[ORM\Entity]
class BerthBuoy
{
    #[ORM\Id]
    #[ORM\Column(length: 40)]
    public ?string $code = null;

    #[ORM\Column]
    public string $label = '';
}
