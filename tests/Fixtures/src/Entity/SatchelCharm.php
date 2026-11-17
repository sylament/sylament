<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A {@see CharmSatchel}'s record keyed by a tier, null until it is assigned, and a code,
 * blank until it is written, constructible without arguments, with a capped label.
 */
#[ORM\Entity]
class SatchelCharm
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        public ?int $tier = null,
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        public string $code = '',
        #[ORM\Column(length: 32)]
        #[Assert\Length(max: 5)]
        public string $label = '',
    ) {
    }
}
