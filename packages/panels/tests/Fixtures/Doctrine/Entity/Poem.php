<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The child of a many-to-many parent: the field completing
 * {@see Anthology::$poems} is a collection, so nothing on a poem points at one
 * anthology and no ancestor can be guessed from it.
 */
#[ORM\Entity]
class Poem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    /**
     * @var Collection<int, Anthology>
     */
    #[ORM\ManyToMany(targetEntity: Anthology::class, inversedBy: 'poems')]
    private Collection $anthologies;

    public function __construct()
    {
        $this->anthologies = new ArrayCollection();
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
     * @return Collection<int, Anthology>
     */
    public function getAnthologies(): Collection
    {
        return $this->anthologies;
    }

    public function addAnthology(Anthology $anthology): static
    {
        if (! $this->anthologies->contains($anthology)) {
            $this->anthologies->add($anthology);
        }

        return $this;
    }
}
