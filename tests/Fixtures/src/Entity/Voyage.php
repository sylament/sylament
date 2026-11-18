<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An item of both of a {@see Dock}'s collections, with a generated key: it arrives at one dock
 * and departs from another.
 */
#[ORM\Entity]
class Voyage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\ManyToOne(inversedBy: 'arrivals')]
    public ?Dock $arrivalDock = null;

    #[ORM\ManyToOne(inversedBy: 'departures')]
    public ?Dock $departureDock = null;

    public function __construct(
        #[ORM\Column(length: 40)]
        public string $name = '',
    ) {
    }
}
