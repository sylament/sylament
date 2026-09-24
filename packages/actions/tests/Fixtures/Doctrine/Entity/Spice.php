<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A single assigned string identifier beside a label its own constraint
 * requires, so a create can fail that constraint and carry a taken key at once.
 */
#[ORM\Entity]
class Spice
{
    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private string $name = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $label = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }
}
