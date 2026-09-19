<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A single `decimal` identifier: the third type whose own conversion hands a
 * malformed string back untouched.
 */
#[ORM\Entity]
class Tariff
{
    #[ORM\Id]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $rate = '';

    #[ORM\Column]
    private string $label = '';

    public function getRate(): string
    {
        return $this->rate;
    }

    public function setRate(string $rate): void
    {
        $this->rate = $rate;
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
