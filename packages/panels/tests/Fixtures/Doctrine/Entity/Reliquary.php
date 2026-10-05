<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SortDirection;

/**
 * The owning side of a many-to-many a nested create joins before it saves:
 * a refused create must leave this collection as it found it.
 */
#[ORM\Entity]
class Reliquary
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    /**
     * @var Collection<int, Relic>
     */
    #[ORM\ManyToMany(targetEntity: Relic::class)]
    #[ORM\JoinTable(name: 'reliquary_relic')]
    #[ORM\JoinColumn(name: 'reliquary_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'relic_vault', referencedColumnName: 'vault')]
    #[ORM\InverseJoinColumn(name: 'relic_niche', referencedColumnName: 'niche')]
    #[ORM\OrderBy(['label' => SortDirection::Ascending])]
    private Collection $relics;

    public function __construct()
    {
        $this->relics = new ArrayCollection();
    }

    public function getId(): ?int
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

    /**
     * @return Collection<int, Relic>
     */
    public function getRelics(): Collection
    {
        return $this->relics;
    }

    public function addRelic(Relic $relic): static
    {
        if (! $this->relics->contains($relic)) {
            $this->relics->add($relic);
        }

        return $this;
    }

    public function removeRelic(Relic $relic): static
    {
        $this->relics->removeElement($relic);

        return $this;
    }
}
