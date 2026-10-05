<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Schemas\Tests\Fixtures\Doctrine\MarkedCodeType;

/**
 * A record keyed by a {@see MarkedCodeType} field and a plain string, so the strict
 * reader meets a composite key.
 */
#[ORM\Entity]
class MarkedTile
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: MarkedCodeType::NAME, length: 255)]
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
