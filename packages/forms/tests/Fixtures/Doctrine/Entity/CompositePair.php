<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record with a composite identifier, so a single-column key assumption fails
 * against it.
 */
#[ORM\Entity]
class CompositePair
{
    /**
     * The self-referential association `relationship(ignoreRecord: true)` needs
     * over a composite target: the option query's root and the bound record are
     * the same entity, so the record must exclude itself by every key field.
     */
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parent_a', referencedColumnName: 'a')]
    #[ORM\JoinColumn(name: 'parent_b', referencedColumnName: 'b')]
    private ?self $parent = null;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $a,
        #[ORM\Id]
        #[ORM\Column]
        private int $b,
        #[ORM\Column]
        private string $label
    ) {
    }

    public function getA(): int
    {
        return $this->a;
    }

    public function getB(): int
    {
        return $this->b;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): void
    {
        $this->parent = $parent;
    }
}
