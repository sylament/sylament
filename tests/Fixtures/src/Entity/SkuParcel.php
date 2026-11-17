<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by one assigned string called otherwise than `id`, which a convenience
 * `getId()` reads back.
 */
#[ORM\Entity]
class SkuParcel
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        public string $sku,
    ) {
    }

    public function getId(): string
    {
        return $this->sku;
    }
}
