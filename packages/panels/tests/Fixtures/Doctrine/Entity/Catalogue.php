<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SortDirection;

/**
 * The single-identifier root of the composite rig: the parent a composite
 * child hangs under, the owner a relation manager lists through, and half the
 * identifier of both {@see Stamp} and {@see Colophon}.
 */
#[ORM\Entity]
class Catalogue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    /**
     * @var Collection<int, Stamp>
     */
    #[ORM\OneToMany(targetEntity: Stamp::class, mappedBy: 'catalogue')]
    #[ORM\OrderBy(['code' => SortDirection::Ascending])]
    private Collection $stamps;

    public function __construct()
    {
        $this->stamps = new ArrayCollection();
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
     * @return Collection<int, Stamp>
     */
    public function getStamps(): Collection
    {
        return $this->stamps;
    }

    public function addStamp(Stamp $stamp): static
    {
        if (! $this->stamps->contains($stamp)) {
            $this->stamps->add($stamp);
        }

        $stamp->setCatalogue($this);

        return $this;
    }

    public function removeStamp(Stamp $stamp): static
    {
        $this->stamps->removeElement($stamp);

        return $this;
    }
}
