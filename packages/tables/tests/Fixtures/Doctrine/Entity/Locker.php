<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylament\Tables\Tests\Fixtures\Doctrine\LockerCodeType;

/**
 * A record keyed by a single field whose {@see LockerCodeType} stores one
 * spelling and hands back another, so a key read off a raw column and a key
 * minted from the record are two different strings.
 */
#[ORM\Entity]
class Locker
{
    /**
     * @var Collection<int, LockerItem>
     */
    #[ORM\OneToMany(targetEntity: LockerItem::class, mappedBy: 'locker')]
    private Collection $items;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: LockerCodeType::NAME)]
        private string $code,
        #[ORM\Column]
        private string $label
    ) {
        $this->items = new ArrayCollection();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return Collection<int, LockerItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }
}
