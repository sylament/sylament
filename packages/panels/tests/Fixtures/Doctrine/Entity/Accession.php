<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Panels\Tests\Fixtures\Doctrine\AccessionNumberGenerator;

/**
 * A single string identifier a custom generator fills at `persist()`, so an
 * edit form that exposes it can hand a new value to a record the unit of work
 * already keys.
 */
#[ORM\Entity]
class Accession
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: AccessionNumberGenerator::class)]
    #[ORM\Column(length: 40)]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    private string $label = '';

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = (string) $label;

        return $this;
    }
}
