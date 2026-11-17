<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A child of {@see TramDepot::$tariffs} keyed by a decimal and a string, so two spellings
 * of one rate (`1.5`, `1.50`) both decode and both name the same row on every platform.
 */
#[ORM\Entity]
class TramTariff
{
    #[ORM\ManyToOne(inversedBy: 'tariffs')]
    public ?TramDepot $depot = null;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
        public string $rate,
        #[ORM\Id]
        #[ORM\Column(length: 16)]
        public string $zone,
        #[ORM\Column]
        public string $label = '',
    ) {
    }
}
