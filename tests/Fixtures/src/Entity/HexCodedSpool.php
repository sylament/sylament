<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylament\Tests\Fixtures\Schemas\Doctrine\HexCodeType;

/**
 * A record keyed by an integer column it holds as hex, through a reader that is not
 * idempotent, wound onto another of its kind: a root whose scalar identifier converts,
 * with a collection to aggregate or join.
 */
#[ORM\Entity]
class HexCodedSpool
{
    #[ORM\ManyToOne(inversedBy: 'windings')]
    #[ORM\JoinColumn(referencedColumnName: 'code')]
    public ?self $core = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'core')]
    public Collection $windings;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: HexCodeType::NAME)]
        public string $code,
    ) {
        $this->windings = new ArrayCollection();
    }
}
