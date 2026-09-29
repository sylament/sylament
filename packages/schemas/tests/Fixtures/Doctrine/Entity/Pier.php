<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single identifier that is a many-to-one association alone.
 */
#[ORM\Entity]
class Pier
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Dock::class)]
    private ?Dock $dock = null;

    #[ORM\Column(length: 64)]
    private string $note = '';

    public function getDock(): ?Dock
    {
        return $this->dock;
    }

    public function setDock(?Dock $dock): void
    {
        $this->dock = $dock;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
    }
}
