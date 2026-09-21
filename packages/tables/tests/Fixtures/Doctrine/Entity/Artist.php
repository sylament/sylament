<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Artist
{
    /**
     * @var Collection<int, Song>
     */
    #[ORM\OneToMany(targetEntity: Song::class, mappedBy: 'artist')]
    #[ORM\OrderBy(['title' => 'ASC'])]
    private Collection $songs;

    /**
     * Ordered **descending**, the one mapping order in either fixture repo that
     * is: it is what tells the default sort's direction map apart from a
     * hardcoded `asc`.
     *
     * @var Collection<int, Album>
     */
    #[ORM\OneToMany(targetEntity: Album::class, mappedBy: 'artist', orphanRemoval: true)]
    #[ORM\OrderBy(['title' => 'DESC'])]
    private Collection $albums;

    /**
     * The inverse side of a many-to-many, which Doctrine gives a `mappedBy` just
     * as it does an inverse one-to-many — the shape a null test for that field
     * would wave through.
     *
     * @var Collection<int, Song>
     */
    #[ORM\ManyToMany(targetEntity: Song::class, mappedBy: 'collaborators')]
    private Collection $collaborations;


    /**
     * A many-to-many onto a scalar-pair target, owned here, ordered by the
     * target's own label so a relation-scoped table has a default sort to read
     * off the mapping.
     *
     * @var Collection<int, Shortcut>
     */
    #[ORM\ManyToMany(targetEntity: Shortcut::class)]
    #[ORM\JoinTable(name: 'artist_shortcut')]
    #[ORM\JoinColumn(name: 'artist_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'shortcut_host', referencedColumnName: 'host')]
    #[ORM\InverseJoinColumn(name: 'shortcut_path', referencedColumnName: 'path')]
    #[ORM\OrderBy(['label' => 'ASC'])]
    private Collection $shortcuts;

    /**
     * The same pair of classes from the inverse side: the composite record owns
     * the join table, so a single-identifier owner reaches its targets through a
     * mapping that names a `mappedBy`.
     *
     * @var Collection<int, Shortcut>
     */
    #[ORM\ManyToMany(targetEntity: Shortcut::class, mappedBy: 'troupes')]
    private Collection $billedShortcuts;

    /**
     * A many-to-many onto a derived identity, owned here.
     *
     * @var Collection<int, Residency>
     */
    #[ORM\ManyToMany(targetEntity: Residency::class)]
    #[ORM\JoinTable(name: 'artist_residency')]
    #[ORM\JoinColumn(name: 'artist_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'residency_venue_id', referencedColumnName: 'venue_id')]
    #[ORM\InverseJoinColumn(name: 'residency_season', referencedColumnName: 'season')]
    private Collection $residencies;

    /**
     * The derived identity from the inverse side.
     *
     * @var Collection<int, Residency>
     */
    #[ORM\ManyToMany(targetEntity: Residency::class, mappedBy: 'bookers')]
    private Collection $bookedResidencies;

    /**
     * A many-to-many onto a single derived identity — one identifier field, and
     * it is an association, so `isIdentifierComposite` is false here.
     *
     * @var Collection<int, LockerSeal>
     */
    #[ORM\ManyToMany(targetEntity: LockerSeal::class)]
    #[ORM\JoinTable(name: 'artist_locker_seal')]
    #[ORM\JoinColumn(name: 'artist_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'seal_locker_id', referencedColumnName: 'locker_id')]
    private Collection $seals;

    /**
     * A many-to-many onto a single identifier that hydrates as an object, so a
     * projection reading the identifier off a row meets it unwrapped.
     *
     * @var Collection<int, Venue>
     */
    #[ORM\ManyToMany(targetEntity: Venue::class)]
    #[ORM\JoinTable(name: 'artist_venue')]
    #[ORM\JoinColumn(name: 'artist_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'venue_handle', referencedColumnName: 'handle')]
    private Collection $venues;

    /**
     * A composite target of an ordinary nullable one-to-many — the mapped-by
     * field is no part of the target's identifier, so associating and
     * dissociating one stays the feature it is.
     *
     * @var Collection<int, Shortcut>
     */
    #[ORM\OneToMany(targetEntity: Shortcut::class, mappedBy: 'curator')]
    private Collection $curatedShortcuts;

    /**
     * A single identifier whose stored spelling differs from the one a record
     * hands back, under a nullable one-to-many, so a dissociation by key has a
     * target whose column form and printed form are two different strings.
     *
     * @var Collection<int, Locker>
     */
    #[ORM\OneToMany(targetEntity: Locker::class, mappedBy: 'keeper')]
    private Collection $keptLockers;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\Column]
        private string $name,
        #[ORM\ManyToOne(targetEntity: Label::class)]
        private ?Label $label = null,
        #[ORM\Embedded(class: Address::class)]
        private Address $address = new Address()
    ) {
        $this->songs = new ArrayCollection();
        $this->albums = new ArrayCollection();
        $this->collaborations = new ArrayCollection();
        $this->shortcuts = new ArrayCollection();
        $this->billedShortcuts = new ArrayCollection();
        $this->residencies = new ArrayCollection();
        $this->bookedResidencies = new ArrayCollection();
        $this->seals = new ArrayCollection();
        $this->venues = new ArrayCollection();
        $this->curatedShortcuts = new ArrayCollection();
        $this->keptLockers = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLabel(): ?Label
    {
        return $this->label;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @return Collection<int, Song>
     */
    public function getSongs(): Collection
    {
        return $this->songs;
    }

    /**
     * @return Collection<int, Album>
     */
    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    /**
     * @return Collection<int, Song>
     */
    public function getCollaborations(): Collection
    {
        return $this->collaborations;
    }

    public function addSong(Song $song): static
    {
        if (!$this->songs->contains($song)) {
            $this->songs->add($song);
        }

        $song->setArtist($this);

        return $this;
    }

    public function removeSong(Song $song): static
    {
        $this->songs->removeElement($song);

        if ($song->getArtist() === $this) {
            $song->setArtist(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Shortcut>
     */
    public function getShortcuts(): Collection
    {
        return $this->shortcuts;
    }

    public function addShortcut(Shortcut $shortcut): static
    {
        if (!$this->shortcuts->contains($shortcut)) {
            $this->shortcuts->add($shortcut);
        }

        return $this;
    }

    public function removeShortcut(Shortcut $shortcut): static
    {
        $this->shortcuts->removeElement($shortcut);

        return $this;
    }

    /**
     * @return Collection<int, Shortcut>
     */
    public function getBilledShortcuts(): Collection
    {
        return $this->billedShortcuts;
    }

    public function addBilledShortcut(Shortcut $billedShortcut): static
    {
        if (!$this->billedShortcuts->contains($billedShortcut)) {
            $this->billedShortcuts->add($billedShortcut);
        }

        return $this;
    }

    public function removeBilledShortcut(Shortcut $billedShortcut): static
    {
        $this->billedShortcuts->removeElement($billedShortcut);

        return $this;
    }

    /**
     * @return Collection<int, Residency>
     */
    public function getResidencies(): Collection
    {
        return $this->residencies;
    }

    public function addResidency(Residency $residency): static
    {
        if (!$this->residencies->contains($residency)) {
            $this->residencies->add($residency);
        }

        return $this;
    }

    public function removeResidency(Residency $residency): static
    {
        $this->residencies->removeElement($residency);

        return $this;
    }

    /**
     * @return Collection<int, Residency>
     */
    public function getBookedResidencies(): Collection
    {
        return $this->bookedResidencies;
    }

    public function addBookedResidency(Residency $bookedResidency): static
    {
        if (!$this->bookedResidencies->contains($bookedResidency)) {
            $this->bookedResidencies->add($bookedResidency);
        }

        return $this;
    }

    public function removeBookedResidency(Residency $bookedResidency): static
    {
        $this->bookedResidencies->removeElement($bookedResidency);

        return $this;
    }

    /**
     * @return Collection<int, LockerSeal>
     */
    public function getSeals(): Collection
    {
        return $this->seals;
    }

    public function addSeal(LockerSeal $seal): static
    {
        if (!$this->seals->contains($seal)) {
            $this->seals->add($seal);
        }

        return $this;
    }

    public function removeSeal(LockerSeal $seal): static
    {
        $this->seals->removeElement($seal);

        return $this;
    }

    /**
     * @return Collection<int, Venue>
     */
    public function getVenues(): Collection
    {
        return $this->venues;
    }

    public function addVenue(Venue $venue): static
    {
        if (!$this->venues->contains($venue)) {
            $this->venues->add($venue);
        }

        return $this;
    }

    public function removeVenue(Venue $venue): static
    {
        $this->venues->removeElement($venue);

        return $this;
    }

    /**
     * @return Collection<int, Shortcut>
     */
    public function getCuratedShortcuts(): Collection
    {
        return $this->curatedShortcuts;
    }

    public function addCuratedShortcut(Shortcut $curatedShortcut): static
    {
        if (!$this->curatedShortcuts->contains($curatedShortcut)) {
            $this->curatedShortcuts->add($curatedShortcut);
        }

        return $this;
    }

    public function removeCuratedShortcut(Shortcut $curatedShortcut): static
    {
        $this->curatedShortcuts->removeElement($curatedShortcut);

        return $this;
    }

    /**
     * @return Collection<int, Locker>
     */
    public function getKeptLockers(): Collection
    {
        return $this->keptLockers;
    }

    public function addKeptLocker(Locker $keptLocker): static
    {
        if (!$this->keptLockers->contains($keptLocker)) {
            $this->keptLockers->add($keptLocker);
        }

        return $this;
    }

    public function removeKeptLocker(Locker $keptLocker): static
    {
        $this->keptLockers->removeElement($keptLocker);

        return $this;
    }
}
