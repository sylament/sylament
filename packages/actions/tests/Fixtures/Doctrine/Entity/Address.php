<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A value object embedded in `Tagged` — the replicator clones one rather than
 * sharing it, so a case can tell the two apart by object identity — carrying a
 * second embeddable of its own, which the clone has to reach through `Geo`'s
 * metadata rather than through `Tagged`'s.
 */
#[ORM\Embeddable]
class Address
{
    #[ORM\Embedded(class: Geo::class)]
    private Geo $geo;

    public function __construct(
        #[ORM\Column(length: 255)]
        private string $street = '',
        ?Geo $geo = null
    ) {
        $this->geo = $geo ?? new Geo();
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    public function getGeo(): Geo
    {
        return $this->geo;
    }

    public function setGeo(Geo $geo): void
    {
        $this->geo = $geo;
    }
}
