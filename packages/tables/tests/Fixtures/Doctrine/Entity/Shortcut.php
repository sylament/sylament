<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SortDirection;

/**
 * A record keyed by two string fields, seeded with the characters a row key has
 * to survive — a dot, a slash, a space, the separator itself and non-ASCII — so
 * a key that does not escape them is caught.
 */
#[ORM\Entity]
class Shortcut
{
    /**
     * @var Collection<int, ShortcutHit>
     */
    #[ORM\OneToMany(targetEntity: ShortcutHit::class, mappedBy: 'shortcut')]
    private Collection $hits;

    /**
     * The same arm as {@see $hits} with a child that can be re-pointed, and the
     * one composite-owner collection in this repo whose mapping declares its own
     * order — so a relation-scoped table rooted here has a default sort to read.
     *
     * @var Collection<int, ShortcutNote>
     */
    #[ORM\OneToMany(targetEntity: ShortcutNote::class, mappedBy: 'shortcut')]
    #[ORM\OrderBy(['heading' => SortDirection::Ascending])]
    private Collection $notes;

    /**
     * The inverse side of a many-to-many a record of several fields owns from
     * the far end: {@see Artist::$shortcuts} holds the join table, so listing
     * this collection scopes through the owner's own association rather than
     * through a mapped-by field of the target.
     *
     * @var Collection<int, Artist>
     */
    #[ORM\ManyToMany(targetEntity: Artist::class, mappedBy: 'shortcuts')]
    private Collection $billedBy;

    /**
     * The owning side of a many-to-many whose target is the single-identifier
     * record: the composite record holds the join table, so the other class
     * reaches this one through a mapping that names a `mappedBy`.
     *
     * @var Collection<int, Artist>
     */
    #[ORM\ManyToMany(targetEntity: Artist::class, inversedBy: 'billedShortcuts')]
    #[ORM\JoinTable(name: 'shortcut_artist')]
    #[ORM\JoinColumn(name: 'shortcut_host', referencedColumnName: 'host')]
    #[ORM\JoinColumn(name: 'shortcut_path', referencedColumnName: 'path')]
    #[ORM\InverseJoinColumn(name: 'artist_id', referencedColumnName: 'id')]
    private Collection $troupes;

    /**
     * An ordinary nullable to-one that is no part of the identifier, so this
     * record can be pointed at another owner without its key moving.
     */
    #[ORM\ManyToOne(targetEntity: Artist::class, inversedBy: 'curatedShortcuts')]
    private ?Artist $curator = null;

    #[ORM\Id]
    #[ORM\Column(length: 100)]
    private string $host = '';

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private string $path = '';

    #[ORM\Column(length: 255)]
    private string $label = '';

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $active = false;

    public function __construct()
    {
        $this->hits = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->billedBy = new ArrayCollection();
        $this->troupes = new ArrayCollection();
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
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
     * @return Collection<int, ShortcutHit>
     */
    public function getHits(): Collection
    {
        return $this->hits;
    }

    /**
     * @return Collection<int, ShortcutNote>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(ShortcutNote $note): void
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
        }

        $note->setShortcut($this);
    }

    public function removeNote(ShortcutNote $note): void
    {
        $this->notes->removeElement($note);

        if ($note->getShortcut() === $this) {
            $note->setShortcut(null);
        }
    }

    /**
     * @return Collection<int, Artist>
     */
    public function getBilledBy(): Collection
    {
        return $this->billedBy;
    }

    public function getCurator(): ?Artist
    {
        return $this->curator;
    }

    public function setCurator(?Artist $curator): void
    {
        $this->curator = $curator;
    }

    /**
     * @return Collection<int, Artist>
     */
    public function getTroupes(): Collection
    {
        return $this->troupes;
    }

    public function addTroupe(Artist $troupe): void
    {
        if (!$this->troupes->contains($troupe)) {
            $this->troupes->add($troupe);
        }
    }

    public function removeTroupe(Artist $troupe): void
    {
        $this->troupes->removeElement($troupe);
    }
}
