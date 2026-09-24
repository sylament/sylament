<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single derived identity onto a plinth, reached from the plinth's inverse
 * side — so its one identifier field is also the field that points back at
 * the record the walk arrived from.
 */
#[ORM\Entity]
class PlinthCap
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Plinth::class, inversedBy: 'cap')]
    #[ORM\JoinColumn(name: 'plinth_code', referencedColumnName: 'code')]
    private ?Plinth $plinth = null;

    #[ORM\Column]
    private string $label = '';

    public function getPlinth(): ?Plinth
    {
        return $this->plinth;
    }

    public function setPlinth(?Plinth $plinth): void
    {
        $this->plinth = $plinth;
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
