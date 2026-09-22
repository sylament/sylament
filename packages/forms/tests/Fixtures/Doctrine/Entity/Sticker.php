<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The inverse side of {@see Leaflet::$stickers}, which declares orphanRemoval:
 * a field bound to this collection cannot drop a member without the owning side
 * deleting this record, so the save refuses instead of writing.
 */
#[ORM\Entity]
class Sticker
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $label = '';

    /**
     * @var Collection<int, Leaflet>
     */
    #[ORM\ManyToMany(targetEntity: Leaflet::class, mappedBy: 'stickers')]
    private Collection $leaflets;

    public function __construct()
    {
        $this->leaflets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return Collection<int, Leaflet>
     */
    public function getLeaflets(): Collection
    {
        return $this->leaflets;
    }

    /**
     * Deliberately does NOT call back to {@see Leaflet::addSticker()}, for the
     * reason {@see Tag::addPost()} gives.
     */
    public function addLeaflet(Leaflet $leaflet): void
    {
        if (! $this->leaflets->contains($leaflet)) {
            $this->leaflets->add($leaflet);
        }
    }

    public function removeLeaflet(Leaflet $leaflet): void
    {
        $this->leaflets->removeElement($leaflet);
    }
}
