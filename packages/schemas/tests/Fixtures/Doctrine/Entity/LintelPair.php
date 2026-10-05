<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Two assigned string halves and no class-level filler, reached through a
 * holder's cascading to-one — so a record a singular relationship constructs
 * here is keyed by whatever its form wrote and nothing else. The second half's
 * property is nullable, so an empty field writes null onto it rather than
 * failing the mapping.
 */
#[ORM\Entity]
class LintelPair
{
    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $a = '';

    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private ?string $b = null;

    #[ORM\Column]
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
