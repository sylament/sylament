<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Forms\Tests\Fixtures\Doctrine\EmblemCodeType;

/**
 * A single assigned identifier under a converting type: the code is written as
 * typed and read back upper case.
 */
#[ORM\Entity]
class Emblem
{
    #[ORM\Id]
    #[ORM\Column(type: EmblemCodeType::NAME, length: 40)]
    private string $code = '';

    #[ORM\Column(length: 40)]
    private string $label = '';

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}
