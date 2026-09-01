<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The child of an owning-side to-many: {@see Anthology::$folios} names this
 * side rather than being named by it, so the route parameter comes off the
 * mapping's inverse field.
 */
#[ORM\Entity]
class Folio
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
    #[ORM\ManyToMany(targetEntity: Anthology::class, mappedBy: 'folios')]
    private Collection $collections;

    public function __construct()
    {
        $this->collections = new ArrayCollection();
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
    public function getCollections(): Collection
    {
        return $this->collections;
    }
}
