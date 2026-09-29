<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A composite identifier of a smallint beside an ascii_string.
 */
#[ORM\Entity]
class Microcode
{
    #[ORM\Id]
    #[ORM\Column(type: Types::SMALLINT)]
    private int $a = 0;

    #[ORM\Id]
    #[ORM\Column(type: Types::ASCII_STRING, length: 8)]
    private string $b = '';

    public function getA(): int
    {
        return $this->a;
    }

    public function setA(int $a): void
    {
        $this->a = $a;
    }

    public function getB(): string
    {
        return $this->b;
    }

    public function setB(string $b): void
    {
        $this->b = $b;
    }
}
