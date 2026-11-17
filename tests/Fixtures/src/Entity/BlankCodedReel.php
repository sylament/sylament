<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by one assigned string whose property starts blank, with no hook that
 * could fill it, so an instance built without its constructor is keyed by `''`.
 */
#[ORM\Entity]
class BlankCodedReel
{
    #[ORM\Id]
    #[ORM\Column(length: 32)]
    public string $code = '';

    #[ORM\Column]
    public string $label = '';
}
