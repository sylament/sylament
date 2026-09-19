<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The to-many a {@see Shortcut} owns: a scalar-pair root reaches a collection
 * of its own here, so an aggregate, a relation search and a fetch join all have
 * a hop that does not run through a shared to-one.
 */
#[ORM\Entity]
class ShortcutHit
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\Column]
        private string $agent,
        #[ORM\Column]
        private int $visits,
        #[ORM\ManyToOne(targetEntity: Shortcut::class, inversedBy: 'hits')]
        #[ORM\JoinColumn(name: 'shortcut_host', referencedColumnName: 'host')]
        #[ORM\JoinColumn(name: 'shortcut_path', referencedColumnName: 'path')]
        private ?Shortcut $shortcut = null
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAgent(): string
    {
        return $this->agent;
    }

    public function getVisits(): int
    {
        return $this->visits;
    }

    public function getShortcut(): ?Shortcut
    {
        return $this->shortcut;
    }
}
