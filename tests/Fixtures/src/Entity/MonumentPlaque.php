<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A translation-shaped record keyed by the {@see Monument} and the locale its constructor takes, neither of which a
 * setter can change.
 */
#[ORM\Entity]
class MonumentPlaque
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(inversedBy: 'plaques')]
        public readonly Monument $monument,
        #[ORM\Id]
        #[ORM\Column(length: 8)]
        public readonly string $locale,
        #[ORM\Column]
        public string $inscription = '',
    ) {
    }
}
