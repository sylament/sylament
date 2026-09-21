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

    /**
     * The inverse of a to-one that is half {@see LockerLease}'s identifier, so a
     * relationship exists whose records cannot be pointed at another owner
     * without their primary key moving.
     *
     * @var Collection<int, LockerLease>
     */
    #[ORM\OneToMany(targetEntity: LockerLease::class, mappedBy: 'locker')]
    private Collection $leases;

    /**
     * An ordinary nullable to-one, so this record can be associated and
     * dissociated while its own identifier stays put.
     */
    #[ORM\ManyToOne(targetEntity: Artist::class, inversedBy: 'keptLockers')]
    private ?Artist $keeper = null;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: LockerCodeType::NAME)]
        private string $code,
        #[ORM\Column]
        private string $label
    ) {
        $this->items = new ArrayCollection();
        $this->leases = new ArrayCollection();
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

    /**
     * @return Collection<int, LockerLease>
     */
    public function getLeases(): Collection
    {
        return $this->leases;
    }

    public function addLease(LockerLease $lease): void
    {
        if (!$this->leases->contains($lease)) {
            $this->leases->add($lease);
        }
    }

    public function removeLease(LockerLease $lease): void
    {
        $this->leases->removeElement($lease);
    }

    public function getKeeper(): ?Artist
    {
        return $this->keeper;
    }

    public function setKeeper(?Artist $keeper): void
    {
        $this->keeper = $keeper;
    }
}
