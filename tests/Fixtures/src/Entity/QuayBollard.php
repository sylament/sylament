<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Tests\Fixtures\Schemas\Doctrine\CaselessType;

/**
 * A {@see Quay}'s record keyed by a single string its column stores lower-cased,
 * constructible without arguments.
 */
#[ORM\Entity]
class QuayBollard
{
    #[ORM\Id]
    #[ORM\Column(type: CaselessType::NAME, length: 40)]
    public ?string $code = null;

    #[ORM\Column]
    public string $label = '';
}
