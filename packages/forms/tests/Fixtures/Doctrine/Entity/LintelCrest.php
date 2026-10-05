<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Two assigned string halves, the second over a nullable property — so a form
 * field over it is not required, and a blank it submits reaches the record.
 */
#[ORM\Entity]
class LintelCrest
{
    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private string $a = '';

    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private ?string $b = null;

    #[ORM\Column(length: 40)]
    private string $label = '';

    public function getA(): string
    {
        return $this->a;
    }

    public function setA(string $a): void
    {
        $this->a = $a;
    }

    public function getB(): ?string
    {
        return $this->b;
    }

    public function setB(?string $b): void
    {
        $this->b = $b;
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
