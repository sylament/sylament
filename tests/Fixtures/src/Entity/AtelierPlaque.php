<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A translation-shaped record: keyed by the {@see Atelier} it belongs to, whose
 * identifier is generated, beside the locale the application assigns.
 */
#[ORM\Entity]
class AtelierPlaque
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(inversedBy: 'plaques')]
        public Atelier $atelier,
        #[ORM\Id]
        #[ORM\Column(length: 8)]
        public string $locale,
        #[ORM\Column]
        public string $inscription = '',
    ) {
    }
}
