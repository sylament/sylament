<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A {@see Wharf}'s record whose identifier is that wharf, constructible without
 * arguments.
 */
#[ORM\Entity]
class WharfCrane
{
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'crane')]
    #[ORM\JoinColumn(referencedColumnName: 'code')]
    public ?Wharf $wharf = null;

    #[ORM\Column]
    public string $label = '';
}
