<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A record whose identifier is half association — a derived identity onto the
 * object-keyed {@see Venue} — so a row key must follow the association down to
 * its target's own identifier before it has anything printable.
 */
#[ORM\Entity]
class Residency
{
    /**
     * @var Collection<int, ResidencyShow>
     */
    #[ORM\OneToMany(targetEntity: ResidencyShow::class, mappedBy: 'residency')]
    private Collection $shows;

    /**
     * The owning side of a many-to-many the single-identifier class reads back
     * through a `mappedBy`, so the derived-identity flavour is reachable from
     * both arms.
     *
     * @var Collection<int, Artist>
     */
    #[ORM\ManyToMany(targetEntity: Artist::class, inversedBy: 'bookedResidencies')]
    #[ORM\JoinTable(name: 'residency_artist')]
    #[ORM\JoinColumn(name: 'residency_venue_id', referencedColumnName: 'venue_id')]
    #[ORM\JoinColumn(name: 'residency_season', referencedColumnName: 'season')]
    #[ORM\InverseJoinColumn(name: 'artist_id', referencedColumnName: 'id')]
    private Collection $bookers;

    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(referencedColumnName: 'handle')]
    private ?Venue $venue = null;

    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private string $season = '';

    #[ORM\Column(length: 255)]
    private string $label = '';

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $active = false;

    public function __construct()
    {
        $this->shows = new ArrayCollection();
        $this->bookers = new ArrayCollection();
    }

    public function getVenue(): ?Venue
    {
        return $this->venue;
    }

    public function setVenue(?Venue $venue): void
    {
        $this->venue = $venue;
    }

    public function getSeason(): string
    {
        return $this->season;
    }

    public function setSeason(string $season): void
    {
        $this->season = $season;
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

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return Collection<int, ResidencyShow>
     */
    public function getShows(): Collection
    {
        return $this->shows;
    }

    /**
     * @return Collection<int, Artist>
     */
    public function getBookers(): Collection
    {
        return $this->bookers;
    }

    public function addBooker(Artist $booker): void
    {
        if (!$this->bookers->contains($booker)) {
            $this->bookers->add($booker);
        }
    }

    public function removeBooker(Artist $booker): void
    {
        $this->bookers->removeElement($booker);
    }
}
