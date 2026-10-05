<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Tables\Tests\Fixtures\Doctrine\MarkedCodeType;

/**
 * A record keyed by one {@see MarkedCodeType} field, whose reader refuses the
 * value it reads.
 */
#[ORM\Entity]
class MarkedTag
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: MarkedCodeType::NAME, length: 255)]
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
