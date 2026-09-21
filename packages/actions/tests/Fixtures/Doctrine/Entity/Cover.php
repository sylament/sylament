<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single derived identity — one identifier association and nothing else — so
 * the identifier is not composite and still is not the literal `id` a mounted
 * action would read.
 */
#[ORM\Entity]
class Cover
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Roster::class)]
    private ?Roster $roster = null;

    #[ORM\Column(length: 255)]
    private string $note = '';

    public function getRoster(): ?Roster
    {
        return $this->roster;
    }

    public function setRoster(?Roster $roster): void
    {
        $this->roster = $roster;
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
