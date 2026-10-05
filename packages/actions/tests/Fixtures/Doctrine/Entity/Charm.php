<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single assigned string identifier that holds `null` until something sets
 * it, so a create that never maps it is refused by Doctrine inside persist().
 */
#[ORM\Entity]
class Charm
{
    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private string $label = '';

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
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
