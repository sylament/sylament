<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The owner a relation-scoped create attaches into, both ways at once: a
 * many-to-many it owns, whose collection an attach writes before the record is
 * mapped, and a one-to-many whose children take it as half their identifier.
 */
#[ORM\Entity]
class Pantry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    /**
     * @var Collection<int, Spice>
     */
    #[ORM\ManyToMany(targetEntity: Spice::class)]
    #[ORM\JoinTable(name: 'pantry_spice')]
    #[ORM\JoinColumn(name: 'pantry_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'spice_name', referencedColumnName: 'name')]
    private Collection $spices;

    /**
     * @var Collection<int, Kettle>
     */
    #[ORM\OneToMany(targetEntity: Kettle::class, mappedBy: 'pantry')]
    private Collection $kettles;

    public function __construct()
    {
        $this->spices = new ArrayCollection();
        $this->kettles = new ArrayCollection();
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
     * @return Collection<int, Spice>
     */
    public function getSpices(): Collection
    {
        return $this->spices;
    }

    public function addSpice(Spice $spice): void
    {
        if (! $this->spices->contains($spice)) {
            $this->spices->add($spice);
        }
    }

    public function removeSpice(Spice $spice): void
    {
        $this->spices->removeElement($spice);
    }

    /**
     * @return Collection<int, Kettle>
     */
    public function getKettles(): Collection
    {
        return $this->kettles;
    }
}
