<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SortDirection;

/**
 * The single-identifier owner of a many-to-many onto composite records: the
 * relation manager lists its imprints, attaches the ones it lacks and detaches
 * the ones it holds, every one of them keyed by a scalar pair.
 */
#[ORM\Entity]
class Gallery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    /**
     * One join column per target identifier column, named explicitly: without
     * both, SchemaTool guesses a single `id` column on the composite target.
     *
     * @var Collection<int, Imprint>
     */
    #[ORM\ManyToMany(targetEntity: Imprint::class)]
    #[ORM\JoinTable(name: 'gallery_imprint')]
    #[ORM\JoinColumn(name: 'gallery_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'imprint_house', referencedColumnName: 'house')]
    #[ORM\InverseJoinColumn(name: 'imprint_year', referencedColumnName: 'year')]
    #[ORM\OrderBy(['label' => SortDirection::Ascending])]
    private Collection $imprints;

    public function __construct()
    {
        $this->imprints = new ArrayCollection();
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
     * @return Collection<int, Imprint>
     */
    public function getImprints(): Collection
    {
        return $this->imprints;
    }

    public function addImprint(Imprint $imprint): static
    {
        if (! $this->imprints->contains($imprint)) {
            $this->imprints->add($imprint);
        }

        return $this;
    }

    public function removeImprint(Imprint $imprint): static
    {
        $this->imprints->removeElement($imprint);

        return $this;
    }
}
