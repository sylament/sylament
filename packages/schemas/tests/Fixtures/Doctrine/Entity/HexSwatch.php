<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Schemas\Tests\Fixtures\Doctrine\HexCodeType;

/**
 * A record keyed by one {@see HexCodeType} field, whose reader is not idempotent.
 */
#[ORM\Entity]
class HexSwatch
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: HexCodeType::NAME)]
        private string $code,
        #[ORM\Column]
        private string $label,
    ) {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
