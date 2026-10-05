<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Two assigned key halves, one nullable and one starting at the empty string,
 * so a form leaving either out reaches a different refusal: Doctrine's own
 * inside persist(), or the create page's blank-key one after it.
 */
#[ORM\Entity]
class Relic
{
    #[ORM\Id]
    #[ORM\Column(length: 100)]
    private ?string $vault = null;

    #[ORM\Id]
    #[ORM\Column(length: 100)]
    private string $niche = '';

    #[ORM\Column(length: 255)]
    private string $label = '';

    public function getVault(): ?string
    {
        return $this->vault;
    }

    public function setVault(?string $vault): static
    {
        $this->vault = $vault;

        return $this;
    }

    public function getNiche(): string
    {
        return $this->niche;
    }

    public function setNiche(string $niche): static
    {
        $this->niche = $niche;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }
}
