<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SortDirection;

/**
 * The scalar pair: two string identifier fields, seeded with the characters
 * the key grammar escapes, and one ordinary to-one so a dotted searchable
 * attribute has a path to walk. It owns both to-many arms as well — a
 * one-to-many of {@see Binding} and a many-to-many of {@see Motif} whose join
 * table carries one owner-side column per identifier field — so a relation
 * manager, a nested resource and a related-records page all run over an owner
 * keyed by two scalars.
 */
#[ORM\Entity]
class Imprint
{
    #[ORM\Id]
    #[ORM\Column(length: 100)]
    private string $house = '';

    #[ORM\Id]
    #[ORM\Column(length: 100)]
    private string $year = '';

    #[ORM\Column(length: 255)]
    private string $label = '';

    #[ORM\ManyToOne(targetEntity: Catalogue::class)]
    private ?Catalogue $catalogue = null;

    /**
     * @var Collection<int, Binding>
     */
    #[ORM\OneToMany(targetEntity: Binding::class, mappedBy: 'imprint')]
    #[ORM\OrderBy(['title' => SortDirection::Ascending])]
    private Collection $bindings;

    /**
     * One owner-side join column per identifier field, named explicitly:
     * without both, SchemaTool guesses a single `id` column for the pair.
     *
     * @var Collection<int, Motif>
     */
    #[ORM\ManyToMany(targetEntity: Motif::class)]
    #[ORM\JoinTable(name: 'imprint_motif')]
    #[ORM\JoinColumn(name: 'imprint_house', referencedColumnName: 'house')]
    #[ORM\JoinColumn(name: 'imprint_year', referencedColumnName: 'year')]
    #[ORM\InverseJoinColumn(name: 'motif_id', referencedColumnName: 'id')]
    #[ORM\OrderBy(['name' => SortDirection::Ascending])]
    private Collection $motifs;

    public function __construct()
    {
        $this->bindings = new ArrayCollection();
        $this->motifs = new ArrayCollection();
    }

    public function getHouse(): string
    {
        return $this->house;
    }

    public function setHouse(string $house): static
    {
        $this->house = $house;

        return $this;
    }

    public function getYear(): string
    {
        return $this->year;
    }

    public function setYear(string $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getCatalogue(): ?Catalogue
    {
        return $this->catalogue;
    }

    public function setCatalogue(?Catalogue $catalogue): static
    {
        $this->catalogue = $catalogue;

        return $this;
    }

    /**
     * @return Collection<int, Binding>
     */
    public function getBindings(): Collection
    {
        return $this->bindings;
    }

    public function addBinding(Binding $binding): static
    {
        if (! $this->bindings->contains($binding)) {
            $this->bindings->add($binding);
        }

        $binding->setImprint($this);

        return $this;
    }

    public function removeBinding(Binding $binding): static
    {
        $this->bindings->removeElement($binding);

        return $this;
    }

    /**
     * @return Collection<int, Motif>
     */
    public function getMotifs(): Collection
    {
        return $this->motifs;
    }

    public function addMotif(Motif $motif): static
    {
        if (! $this->motifs->contains($motif)) {
            $this->motifs->add($motif);
        }

        return $this;
    }

    public function removeMotif(Motif $motif): static
    {
        $this->motifs->removeElement($motif);

        return $this;
    }
}
