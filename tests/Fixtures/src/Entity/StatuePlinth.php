<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A JOINED leaf keyed by its composite root's identifier.
 */
#[ORM\Entity]
class StatuePlinth extends ExhibitPlinth
{
    #[ORM\Column]
    public string $sculptor = '';
}
