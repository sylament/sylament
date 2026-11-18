<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An owner keyed by one generated integer whose {@see MonumentPlaque}s take it in their constructor as half their key.
 */
#[ORM\Entity]
class Monument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, MonumentPlaque>
     */
    #[ORM\OneToMany(targetEntity: MonumentPlaque::class, mappedBy: 'monument')]
    public Collection $plaques;

    public function __construct(
        #[ORM\Column]
        public string $name = '',
    ) {
        $this->plaques = new ArrayCollection();
    }
}
