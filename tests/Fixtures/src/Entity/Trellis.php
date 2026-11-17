<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A record owning its {@see TrellisPanel}s, each of which holds a collection of its own,
 * so a relationship repeater's item maps a child with an object collection.
 */
#[ORM\Entity]
class Trellis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, TrellisPanel>
     */
    #[ORM\OneToMany(targetEntity: TrellisPanel::class, mappedBy: 'trellis', cascade: ['persist'], orphanRemoval: true)]
    public Collection $panels;

    public function __construct()
    {
        $this->panels = new ArrayCollection();
    }

    public function addPanel(TrellisPanel $panel): void
    {
        if (!$this->panels->contains($panel)) {
            $this->panels->add($panel);
            $panel->trellis = $this;
        }
    }

    public function removePanel(TrellisPanel $panel): void
    {
        if ($this->panels->removeElement($panel)) {
            $panel->trellis = null;
        }
    }
}
