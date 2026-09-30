<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A cached owner keyed by one field, whose cached collection of lamps is what
 * a by-key dissociate must leave readable afterwards.
 */
#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class Shelf
{
    /**
     * @var Collection<int, Lamp>
     */
    #[ORM\OneToMany(targetEntity: Lamp::class, mappedBy: 'shelf')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $lamps;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\Column(length: 64)]
        private string $name,
    ) {
        $this->lamps = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<int, Lamp>
     */
    public function getLamps(): Collection
    {
        return $this->lamps;
    }
}
