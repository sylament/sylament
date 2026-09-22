<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Every name a statement writes is a reserved word the mapping quotes — the
 * table, the identifier column and the join column — so a name read past the
 * quote strategy is a syntax error rather than a working statement.
 */
#[ORM\Entity]
#[ORM\Table(name: '`order`')]
class Purchase
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(name: '`group`')]
        private int $id,
        #[ORM\Column]
        private string $label,
        #[ORM\ManyToOne(targetEntity: Bazaar::class, inversedBy: 'purchases')]
        #[ORM\JoinColumn(name: '`select`', referencedColumnName: 'region')]
        #[ORM\JoinColumn(name: '`from`', referencedColumnName: 'code')]
        private ?Bazaar $bazaar = null,
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

    public function getBazaar(): ?Bazaar
    {
        return $this->bazaar;
    }

    public function setBazaar(?Bazaar $bazaar): void
    {
        $this->bazaar = $bazaar;
    }
}
