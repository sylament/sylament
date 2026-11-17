<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by its association to the {@see SkuParcel} it travels with, whose
 * `getId()` reads the parcel's key.
 */
#[ORM\Entity]
class ParcelWaybill
{
    public function __construct(
        #[ORM\Id]
        #[ORM\OneToOne]
        #[ORM\JoinColumn(referencedColumnName: 'sku')]
        public SkuParcel $parcel,
    ) {
    }

    public function getId(): string
    {
        return $this->parcel->sku;
    }
}
