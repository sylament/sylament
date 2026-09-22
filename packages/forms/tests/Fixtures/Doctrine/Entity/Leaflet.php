<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The owning side of the only many-to-many in this fixture tree that declares
 * orphanRemoval: a sticker dropped from the collection is deleted rather than
 * unlinked. Nothing here writes it — {@see Sticker} holds the inverse side, and
 * a field bound to that side is what the save refuses by name.
 */
#[ORM\Entity]
class Leaflet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $title = '';

    /**
     * @var Collection<int, Sticker>
     */
    #[ORM\ManyToMany(targetEntity: Sticker::class, inversedBy: 'leaflets', orphanRemoval: true)]
    private Collection $stickers;

    public function __construct()
    {
        $this->stickers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return Collection<int, Sticker>
     */
    public function getStickers(): Collection
    {
        return $this->stickers;
    }

    public function addSticker(Sticker $sticker): void
    {
        if (! $this->stickers->contains($sticker)) {
            $this->stickers->add($sticker);
        }
    }

    public function removeSticker(Sticker $sticker): void
    {
        $this->stickers->removeElement($sticker);
    }
}
