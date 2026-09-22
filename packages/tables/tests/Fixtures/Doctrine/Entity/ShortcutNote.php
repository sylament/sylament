<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A {@see Shortcut}'s re-pointable child: the mapped-by to-one is nullable and
 * has a setter, so a record of it can be associated into the scalar-pair record
 * that owns it and dissociated out again — which the constructor-promoted
 * {@see ShortcutHit} beside it cannot.
 */
#[ORM\Entity]
class ShortcutNote
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\Column]
        private string $heading,
        #[ORM\Column]
        private int $position,
        #[ORM\ManyToOne(targetEntity: Shortcut::class, inversedBy: 'notes')]
        #[ORM\JoinColumn(name: 'shortcut_host', referencedColumnName: 'host')]
        #[ORM\JoinColumn(name: 'shortcut_path', referencedColumnName: 'path')]
        private ?Shortcut $shortcut = null
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getHeading(): string
    {
        return $this->heading;
    }

    public function setHeading(string $heading): void
    {
        $this->heading = $heading;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getShortcut(): ?Shortcut
    {
        return $this->shortcut;
    }

    public function setShortcut(?Shortcut $shortcut): void
    {
        $this->shortcut = $shortcut;
    }
}
