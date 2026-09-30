<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SortDirection;

/**
 * A composite identifier one of whose fields is literally named `id` — the
 * translation-table shape an action mints a partial key for when it reads a
 * record's `id` property and calls it the whole one. Its {@see Variant}
 * collection makes it a relation owner as well, which is the flavour where a
 * parent segment spelled `5` is a plausible URL for half a key.
 */
#[ORM\Entity]
class Edition
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 0;

    #[ORM\Id]
    #[ORM\Column(length: 12)]
    private string $locale = '';

    #[ORM\Column(length: 255)]
    private string $title = '';

    /**
     * @var Collection<int, Variant>
     */
    #[ORM\OneToMany(targetEntity: Variant::class, mappedBy: 'edition')]
    #[ORM\OrderBy(['name' => SortDirection::Ascending])]
    private Collection $variants;

    public function __construct()
    {
        $this->variants = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
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
     * @return Collection<int, Variant>
     */
    public function getVariants(): Collection
    {
        return $this->variants;
    }

    public function addVariant(Variant $variant): static
    {
        if (! $this->variants->contains($variant)) {
            $this->variants->add($variant);
        }

        $variant->setEdition($this);

        return $this;
    }

    public function removeVariant(Variant $variant): static
    {
        $this->variants->removeElement($variant);

        return $this;
    }
}
