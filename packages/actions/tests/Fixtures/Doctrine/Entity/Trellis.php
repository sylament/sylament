<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A join entity keyed by two associations and nothing else: no scalar field
 * of the key is left for a replicate to change, yet neither association alone
 * is the whole identifier.
 */
#[ORM\Entity]
class Trellis
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Note::class)]
    #[ORM\JoinColumn(name: 'note_id', referencedColumnName: 'id')]
    private ?Note $note = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Roster::class)]
    #[ORM\JoinColumn(name: 'roster_id', referencedColumnName: 'id')]
    private ?Roster $roster = null;

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

    public function getRoster(): ?Roster
    {
        return $this->roster;
    }

    public function setRoster(?Roster $roster): void
    {
        $this->roster = $roster;
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
