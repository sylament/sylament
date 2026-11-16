<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A {@see Berth}'s record keyed by that berth and a string, the {@see InvoiceLine}
 * flavour: the berth half is written by {@see Berth::setCleat()}, the string half by the
 * application.
 */
#[ORM\Entity]
class BerthCleat
{
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'cleat')]
    public ?Berth $berth = null;

    #[ORM\Id]
    #[ORM\Column]
    public ?string $slot = null;

    #[ORM\Column]
    public string $label = '';
}
