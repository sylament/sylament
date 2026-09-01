<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The ambiguity rig: two collections of {@see Leaf}, so nothing but an explicit
 * relationship name can say which one a child resource hangs off.
 */
#[ORM\Entity]
class Tome
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    /**
     * @var Collection<int, Leaf>
     */
    #[ORM\OneToMany(targetEntity: Leaf::class, mappedBy: 'tome')]
    private Collection $leaves;

    /**
     * @var Collection<int, Leaf>
     */
    #[ORM\OneToMany(targetEntity: Leaf::class, mappedBy: 'appendixTome')]
    private Collection $appendixLeaves;

    public function __construct()
    {
        $this->leaves = new ArrayCollection();
        $this->appendixLeaves = new ArrayCollection();
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
     * @return Collection<int, Leaf>
     */
    public function getLeaves(): Collection
    {
        return $this->leaves;
    }

    /**
     * @return Collection<int, Leaf>
     */
    public function getAppendixLeaves(): Collection
    {
        return $this->appendixLeaves;
    }
}
