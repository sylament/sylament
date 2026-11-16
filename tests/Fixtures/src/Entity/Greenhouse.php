<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * An owner keyed by a uuid under symfony/uid's `uuid` type, whose database form is not
 * its printed form on a platform without a native uuid, with a many-to-many and a
 * one-to-many.
 */
#[ORM\Entity]
class Greenhouse
{
    /**
     * @var Collection<int, Plant>
     */
    #[ORM\ManyToMany(targetEntity: Plant::class)]
    public Collection $plants;

    /**
     * @var Collection<int, Seedling>
     */
    #[ORM\OneToMany(targetEntity: Seedling::class, mappedBy: 'greenhouse')]
    public Collection $seedlings;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'uuid')]
        public Uuid $id,
    ) {
        $this->plants = new ArrayCollection();
        $this->seedlings = new ArrayCollection();
    }
}
