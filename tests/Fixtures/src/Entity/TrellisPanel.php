<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A panel of {@see Trellis::$panels}: a label a form maps, and the {@see Plant}s it holds,
 * an object collection of the child.
 */
#[ORM\Entity]
class TrellisPanel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\ManyToOne(inversedBy: 'panels')]
    public ?Trellis $trellis = null;

    /**
     * @var Collection<int, Plant>
     */
    #[ORM\ManyToMany(targetEntity: Plant::class)]
    public Collection $plants;

    public function __construct(
        #[ORM\Column(length: 32)]
        public string $label = '',
    ) {
        $this->plants = new ArrayCollection();
    }
}
