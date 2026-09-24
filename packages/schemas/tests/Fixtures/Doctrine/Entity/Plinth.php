<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single assigned string identifier holding, on the inverse side of a
 * cascading one-to-one, a cap whose whole identity is the association back to
 * it.
 */
#[ORM\Entity]
class Plinth
{
    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $code = '';

    #[ORM\OneToOne(targetEntity: PlinthCap::class, mappedBy: 'plinth', cascade: ['persist'])]
    private ?PlinthCap $cap = null;

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getCap(): ?PlinthCap
    {
        return $this->cap;
    }

    public function setCap(?PlinthCap $cap): void
    {
        $this->cap = $cap;
    }
}
