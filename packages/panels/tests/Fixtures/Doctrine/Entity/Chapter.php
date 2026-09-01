<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The root of the nested-resource rig: a chapter owning the verses a nested
 * resource lists, which own lines of their own one level deeper.
 */
#[ORM\Entity]
class Chapter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    /**
     * @var Collection<int, Verse>
     */
    #[ORM\OneToMany(targetEntity: Verse::class, mappedBy: 'chapter')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $verses;

    public function __construct()
    {
        $this->verses = new ArrayCollection();
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
     * @return Collection<int, Verse>
     */
    public function getVerses(): Collection
    {
        return $this->verses;
    }

    public function addVerse(Verse $verse): static
    {
        if (! $this->verses->contains($verse)) {
            $this->verses->add($verse);
        }

        $verse->setChapter($this);

        return $this;
    }

    public function removeVerse(Verse $verse): static
    {
        $this->verses->removeElement($verse);

        return $this;
    }
}
