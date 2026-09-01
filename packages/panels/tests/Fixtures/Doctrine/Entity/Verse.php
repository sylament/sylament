<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The middle level of the nested-resource rig: a verse belongs to one
 * {@see Chapter} through a join column that refuses null, and owns the
 * {@see Line}s one level deeper.
 */
#[ORM\Entity]
class Verse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $heading = '';

    #[ORM\Column]
    private int $position = 0;

    #[ORM\ManyToOne(targetEntity: Chapter::class, inversedBy: 'verses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chapter $chapter = null;

    /**
     * @var Collection<int, Line>
     */
    #[ORM\OneToMany(targetEntity: Line::class, mappedBy: 'verse')]
    private Collection $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHeading(): string
    {
        return $this->heading;
    }

    public function setHeading(string $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getChapter(): ?Chapter
    {
        return $this->chapter;
    }

    public function setChapter(?Chapter $chapter): static
    {
        $this->chapter = $chapter;

        return $this;
    }

    /**
     * @return Collection<int, Line>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(Line $line): static
    {
        if (! $this->lines->contains($line)) {
            $this->lines->add($line);
        }

        $line->setVerse($this);

        return $this;
    }

    public function removeLine(Line $line): static
    {
        $this->lines->removeElement($line);

        return $this;
    }
}
