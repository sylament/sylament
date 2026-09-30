<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A cached record that belongs to a shelf keyed by one field and a stand keyed
 * by two, so the by-key dissociate writes it through DQL from the one and
 * through DBAL from the other, and the by-key delete takes it whole.
 */
#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class Lamp
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\Column(length: 64)]
        private string $label,
        #[ORM\ManyToOne(targetEntity: Shelf::class, inversedBy: 'lamps')]
        private ?Shelf $shelf = null,
        #[ORM\ManyToOne(targetEntity: Stand::class, inversedBy: 'lamps')]
        #[ORM\JoinColumn(name: 'stand_region', referencedColumnName: 'region')]
        #[ORM\JoinColumn(name: 'stand_code', referencedColumnName: 'code')]
        private ?Stand $stand = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getShelf(): ?Shelf
    {
        return $this->shelf;
    }

    public function setShelf(?Shelf $shelf): void
    {
        $this->shelf = $shelf;
    }

    public function getStand(): ?Stand
    {
        return $this->stand;
    }

    public function setStand(?Stand $stand): void
    {
        $this->stand = $stand;
    }
}
