<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The single derived identity: one association and nothing else, so Doctrine
 * reports the identifier as not composite while its one value is a record
 * rather than a scalar.
 */
#[ORM\Entity]
class Colophon
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Catalogue::class)]
    private ?Catalogue $catalogue = null;

    #[ORM\Column(length: 255)]
    private string $note = '';

    /**
     * @var Collection<int, ColophonLine>
     */
    #[ORM\OneToMany(targetEntity: ColophonLine::class, mappedBy: 'colophon')]
    private Collection $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
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

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): static
    {
        $this->note = $note;

        return $this;
    }

    /**
     * @return Collection<int, ColophonLine>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(ColophonLine $line): static
    {
        if (! $this->lines->contains($line)) {
            $this->lines->add($line);
        }

        $line->setColophon($this);

        return $this;
    }

    public function removeLine(ColophonLine $line): static
    {
        $this->lines->removeElement($line);

        return $this;
    }
}
