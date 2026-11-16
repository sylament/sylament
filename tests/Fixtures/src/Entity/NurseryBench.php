<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A record with a generated key owning a collection of {@see BenchPot}s, each keyed by
 * two strings of its own.
 */
#[ORM\Entity]
class NurseryBench
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 40)]
    public string $name = '';

    /**
     * @var Collection<int, BenchPot>
     */
    #[ORM\OneToMany(targetEntity: BenchPot::class, mappedBy: 'bench', cascade: ['persist'], orphanRemoval: true)]
    public Collection $pots;

    public function __construct()
    {
        $this->pots = new ArrayCollection();
    }

    public function addPot(BenchPot $pot): void
    {
        if (!$this->pots->contains($pot)) {
            $this->pots->add($pot);
            $pot->bench = $this;
        }
    }

    public function removePot(BenchPot $pot): void
    {
        if ($this->pots->removeElement($pot)) {
            $pot->bench = null;
        }
    }
}
