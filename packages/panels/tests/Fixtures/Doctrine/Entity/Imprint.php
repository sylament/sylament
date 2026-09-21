<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The scalar pair: two string identifier fields, seeded with the characters
 * the key grammar escapes, and one ordinary to-one so a dotted searchable
 * attribute has a path to walk.
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
}
