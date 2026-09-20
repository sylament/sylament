<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The commonest composite key there is — a join entity keyed by two
 * associations and nothing else, so the identifier tiebreak orders a page by
 * two association paths at once. One half targets the {@see Locker} whose
 * {@see \Sylament\Tables\Tests\Fixtures\Doctrine\LockerCodeType} stores one
 * spelling and reads back another, so both the width of the rewrite and the
 * conversion of a key have to hold on the same record.
 */
#[ORM\Entity]
class LockerLease
{
    /**
     * @var Collection<int, LockerLeaseNote>
     */
    #[ORM\OneToMany(targetEntity: LockerLeaseNote::class, mappedBy: 'lease')]
    private Collection $notes;

    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(referencedColumnName: 'code')]
    private ?Locker $locker = null;

    #[ORM\Id]
    #[ORM\ManyToOne]
    private ?Artist $artist = null;

    #[ORM\Column(length: 255)]
    private string $label = '';

    #[ORM\Column]
    private int $position = 0;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
    }

    public function getLocker(): ?Locker
    {
        return $this->locker;
    }

    public function setLocker(?Locker $locker): void
    {
        $this->locker = $locker;
    }

    public function getArtist(): ?Artist
    {
        return $this->artist;
    }

    public function setArtist(?Artist $artist): void
    {
        $this->artist = $artist;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return Collection<int, LockerLeaseNote>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }
}
