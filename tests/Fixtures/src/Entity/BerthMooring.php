<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A {@see Berth}'s record keyed by two strings the application assigns, the
 * {@see OrderLine} flavour, constructible without arguments.
 */
#[ORM\Entity]
class BerthMooring
{
    #[ORM\Id]
    #[ORM\Column(length: 40)]
    public ?string $pier = null;

    #[ORM\Id]
    #[ORM\Column(length: 40)]
    public ?string $slot = null;

    #[ORM\Column]
    public string $label = '';
}
