<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Schemas\Tests\Fixtures\Doctrine\HexCodeType;

/**
 * A record keyed by a {@see HexCodeType} field and a plain string, so the
 * non-idempotent reader meets a composite key.
 */
#[ORM\Entity]
class HexTile
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: HexCodeType::NAME)]
        private string $code,
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        private string $zone,
        #[ORM\Column]
        private string $label,
    ) {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getZone(): string
    {
        return $this->zone;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
