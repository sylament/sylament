<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The many-to-many rig: one parent carrying a to-many of each shape the route
 * parameter is derived from — an inverse side naming the field completing it
 * ({@see $poems}), an owning side naming the inverse field ({@see $folios}),
 * and a unidirectional one naming nothing ({@see $plates}).
 */
#[ORM\Entity]
class Anthology
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    /**
     * @var Collection<int, Poem>
     */
    #[ORM\ManyToMany(targetEntity: Poem::class, mappedBy: 'anthologies')]
    private Collection $poems;

    /**
     * @var Collection<int, Folio>
     */
    #[ORM\ManyToMany(targetEntity: Folio::class, inversedBy: 'collections')]
    private Collection $folios;

    /**
     * @var Collection<int, Plate>
     */
    #[ORM\ManyToMany(targetEntity: Plate::class)]
    private Collection $plates;

    public function __construct()
    {
        $this->poems = new ArrayCollection();
        $this->folios = new ArrayCollection();
        $this->plates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection<int, Poem>
     */
    public function getPoems(): Collection
    {
        return $this->poems;
    }

    /**
     * @return Collection<int, Folio>
     */
    public function getFolios(): Collection
    {
        return $this->folios;
    }

    /**
     * @return Collection<int, Plate>
     */
    public function getPlates(): Collection
    {
        return $this->plates;
    }
}
