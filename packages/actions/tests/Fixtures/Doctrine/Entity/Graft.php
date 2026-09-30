<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A note plus a stage, the stage declaring a default: a bare replicate copies
 * the note and leaves the stage at its default, so a copy of a record still on
 * that default carries its original's key.
 */
#[ORM\Entity]
class Graft
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Note::class)]
    #[ORM\JoinColumn(name: 'note_id', referencedColumnName: 'id')]
    private ?Note $note = null;

    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $stage = 'rootstock';

    #[ORM\Column(length: 255)]
    private string $label = '';

    public function getNote(): ?Note
    {
        return $this->note;
    }

    public function setNote(?Note $note): void
    {
        $this->note = $note;
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    public function setStage(string $stage): void
    {
        $this->stage = $stage;
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
