<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylament\Panels\Tests\Fixtures\Doctrine\StampCodeType;

/**
 * The derived identity: one {@see Catalogue} — which is also the parent link a
 * nested resource and a relation manager scope through — plus a code the
 * column stores in lower case and the record reads back upper case, so a key
 * minted off a raw column value is a different string from the record's.
 */
#[ORM\Entity]
class Stamp
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Catalogue::class, inversedBy: 'stamps')]
    private ?Catalogue $catalogue = null;

    #[ORM\Id]
    #[ORM\Column(type: StampCodeType::NAME, length: 40)]
    private string $code = '';

    #[ORM\Column(length: 255)]
    private string $caption = '';

    /**
     * @var Collection<int, StampMark>
     */
    #[ORM\OneToMany(targetEntity: StampMark::class, mappedBy: 'stamp')]
    private Collection $marks;

    public function __construct()
    {
        $this->marks = new ArrayCollection();
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

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getCaption(): string
    {
        return $this->caption;
    }

    public function setCaption(string $caption): static
    {
        $this->caption = $caption;

        return $this;
    }

    /**
     * @return Collection<int, StampMark>
     */
    public function getMarks(): Collection
    {
        return $this->marks;
    }

    public function addMark(StampMark $mark): static
    {
        if (! $this->marks->contains($mark)) {
            $this->marks->add($mark);
        }

        $mark->setStamp($this);

        return $this;
    }

    public function removeMark(StampMark $mark): static
    {
        $this->marks->removeElement($mark);

        return $this;
    }
}
