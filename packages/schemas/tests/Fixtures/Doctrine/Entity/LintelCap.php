<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single derived identity onto its base, reached from the base's inverse
 * side: its whole key is the association the base's setter fills.
 */
#[ORM\Entity]
class LintelCap
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: LintelBase::class, inversedBy: 'cap')]
    private ?LintelBase $base = null;

    #[ORM\Column]
    private string $label = '';

    public function getBase(): ?LintelBase
    {
        return $this->base;
    }

    public function setBase(?LintelBase $base): void
    {
        $this->base = $base;
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
