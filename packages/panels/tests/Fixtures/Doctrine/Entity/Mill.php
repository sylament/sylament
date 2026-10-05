<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The parent a {@see Countermark} nests under and takes in its constructor.
 */
#[ORM\Entity]
class Mill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    /**
     * @var Collection<int, Countermark>
     */
    #[ORM\OneToMany(targetEntity: Countermark::class, mappedBy: 'mill')]
    private Collection $countermarks;

    public function __construct()
    {
        $this->countermarks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Countermark>
     */
    public function getCountermarks(): Collection
    {
        return $this->countermarks;
    }
}
