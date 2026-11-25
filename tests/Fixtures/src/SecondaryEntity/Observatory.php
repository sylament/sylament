<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\SecondaryEntity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An owner of {@see Telescope}s, mapped by the `secondary` manager only.
 */
#[ORM\Entity]
class Observatory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, Telescope>
     */
    #[ORM\OneToMany(targetEntity: Telescope::class, mappedBy: 'observatory', cascade: ['persist'], orphanRemoval: true)]
    public Collection $telescopes;

    public function __construct(
        #[ORM\Column(length: 40)]
        public string $name = '',
    ) {
        $this->telescopes = new ArrayCollection();
    }

    public function addTelescope(Telescope $telescope): void
    {
        if (!$this->telescopes->contains($telescope)) {
            $this->telescopes->add($telescope);
            $telescope->observatory = $this;
        }
    }
}
