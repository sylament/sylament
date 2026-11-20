<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SortDirection;

/**
 * An owner with a generated key whose mapped collection of {@see TrellisVine}s, ordered by
 * name, is read through a getter that answers a plain array rather than the collection.
 */
#[ORM\Entity]
class Trellis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, TrellisVine>
     */
    #[ORM\OneToMany(targetEntity: TrellisVine::class, mappedBy: 'trellis', cascade: ['persist'])]
    #[ORM\OrderBy(['name' => SortDirection::Ascending])]
    private Collection $vines;

    public function __construct(
        #[ORM\Column(length: 40)]
        public string $name = '',
    ) {
        $this->vines = new ArrayCollection();
    }

    public function addVine(TrellisVine $vine): void
    {
        if (!$this->vines->contains($vine)) {
            $this->vines->add($vine);
            $vine->trellis = $this;
        }
    }

    /**
     * @return array<int, TrellisVine>
     */
    public function getVines(): array
    {
        return $this->vines->toArray();
    }
}
