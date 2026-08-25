<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An entity whose composite identifier is half association: the replica carries
 * neither half, so persisting it fails at Doctrine's own missing-identifier
 * check rather than at a duplicate key.
 */
#[ORM\Entity]
class Membership
{
    #[ORM\Id]
    #[ORM\ManyToOne]
    private ?Note $note = null;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private string $role = '';

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

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
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
