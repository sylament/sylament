<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by two strings the application assigns, both null until a form writes
 * them, so a form need not require either.
 */
#[ORM\Entity]
class HostPathAlias
{
    #[ORM\Id]
    #[ORM\Column(length: 100)]
    public ?string $host = null;

    #[ORM\Id]
    #[ORM\Column(length: 100)]
    public ?string $path = null;

    #[ORM\Column]
    public string $label = '';
}
