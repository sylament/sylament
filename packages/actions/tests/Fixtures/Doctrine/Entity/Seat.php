<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A derived identity whose identifier association *is* the relationship a
 * create action attaches: until the attach runs, `roster` reads unset and
 * Doctrine's assigned-identifier generator refuses the record.
 */
#[ORM\Entity]
class Seat
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Roster::class, inversedBy: 'seats')]
    private ?Roster $roster = null;

    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private string $code = '';

    #[ORM\Column(length: 255)]
    private string $label = '';

    public function getRoster(): ?Roster
    {
        return $this->roster;
    }

    public function setRoster(?Roster $roster): void
    {
        $this->roster = $roster;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
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
