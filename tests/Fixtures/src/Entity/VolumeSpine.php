<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The related record whose assigned code is the whole key of a {@see BoundVolume}.
 */
#[ORM\Entity]
class VolumeSpine
{
    #[ORM\Id]
    #[ORM\Column(length: 32)]
    public string $code = '';

    #[ORM\Column]
    public string $label = '';
}
