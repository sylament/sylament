<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An owner relating to records keyed through a scalar type a value read twice is
 * not the same value for — {@see HexSwatch} and {@see HexTile} through the
 * lenient hex reader, {@see MarkedTag} through the strict prefixed one — by a
 * many-to-many it owns and by a one-to-many the target's keeper maps.
 */
#[ORM\Entity]
class HexOwner
{
    /**
     * @var Collection<int, HexSwatch>
     */
    #[ORM\ManyToMany(targetEntity: HexSwatch::class, inversedBy: 'owners')]
    #[ORM\JoinTable(name: 'hex_owner_swatch')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'swatch_code', referencedColumnName: 'code')]
    private Collection $swatches;

    /**
     * @var Collection<int, HexTile>
     */
    #[ORM\ManyToMany(targetEntity: HexTile::class, inversedBy: 'owners')]
    #[ORM\JoinTable(name: 'hex_owner_tile')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'tile_code', referencedColumnName: 'code')]
    #[ORM\InverseJoinColumn(name: 'tile_zone', referencedColumnName: 'zone')]
    private Collection $tiles;

    /**
     * @var Collection<int, MarkedTag>
     */
    #[ORM\ManyToMany(targetEntity: MarkedTag::class)]
    #[ORM\JoinTable(name: 'hex_owner_marked_tag')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'tag_code', referencedColumnName: 'code')]
    private Collection $markedTags;

    /**
     * @var Collection<int, HexSwatch>
     */
    #[ORM\OneToMany(targetEntity: HexSwatch::class, mappedBy: 'keeper')]
    private Collection $keptSwatches;

    /**
     * @var Collection<int, HexTile>
     */
    #[ORM\OneToMany(targetEntity: HexTile::class, mappedBy: 'keeper')]
    private Collection $keptTiles;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\Column]
        private string $name,
    ) {
        $this->swatches = new ArrayCollection();
        $this->tiles = new ArrayCollection();
        $this->markedTags = new ArrayCollection();
        $this->keptSwatches = new ArrayCollection();
        $this->keptTiles = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<int, HexSwatch>
     */
    public function getSwatches(): Collection
    {
        return $this->swatches;
    }

    public function addSwatch(HexSwatch $swatch): void
    {
        if (!$this->swatches->contains($swatch)) {
            $this->swatches->add($swatch);
        }
    }

    public function removeSwatch(HexSwatch $swatch): void
    {
        $this->swatches->removeElement($swatch);
    }

    /**
     * @return Collection<int, HexTile>
     */
    public function getTiles(): Collection
    {
        return $this->tiles;
    }

    public function addTile(HexTile $tile): void
    {
        if (!$this->tiles->contains($tile)) {
            $this->tiles->add($tile);
        }
    }

    public function removeTile(HexTile $tile): void
    {
        $this->tiles->removeElement($tile);
    }

    /**
     * @return Collection<int, MarkedTag>
     */
    public function getMarkedTags(): Collection
    {
        return $this->markedTags;
    }

    public function addMarkedTag(MarkedTag $markedTag): void
    {
        if (!$this->markedTags->contains($markedTag)) {
            $this->markedTags->add($markedTag);
        }
    }

    public function removeMarkedTag(MarkedTag $markedTag): void
    {
        $this->markedTags->removeElement($markedTag);
    }

    /**
     * @return Collection<int, HexSwatch>
     */
    public function getKeptSwatches(): Collection
    {
        return $this->keptSwatches;
    }

    /**
     * @return Collection<int, HexTile>
     */
    public function getKeptTiles(): Collection
    {
        return $this->keptTiles;
    }
}
