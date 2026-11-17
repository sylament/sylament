<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The root of a single-table hierarchy whose {@see TramDialGauge} leaf alone belongs to a
 * {@see TramDepot}, so a statement over the leaf carries the discriminator.
 */
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'kind', type: 'string', length: 16)]
#[ORM\DiscriminatorMap(['gauge' => TramGauge::class, 'dial' => TramDialGauge::class])]
class TramGauge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    public function __construct(
        #[ORM\Column]
        public string $name = '',
    ) {
    }
}
