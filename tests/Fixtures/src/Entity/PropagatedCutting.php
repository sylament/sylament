<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A cutting propagated from another cutting: a generated id, a self-referential to-one
 * onto the cutting it came from, and the nursery it was taken at as an embeddable.
 */
#[ORM\Entity]
class PropagatedCutting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\ManyToOne]
    public ?self $parent = null;

    #[ORM\Embedded]
    public CuttingOrigin $origin;

    public function __construct(
        #[ORM\Column]
        public string $name = '',
        string $nursery = '',
    ) {
        $this->origin = new CuttingOrigin($nursery);
    }
}
