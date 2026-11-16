<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An owner of two to-one records a select creates as options, each keyed by what the
 * application assigns: one by a string its column stores lower-cased, one by two strings
 * whose prePersist callback fills neither.
 */
#[ORM\Entity]
class Quay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(referencedColumnName: 'code')]
    public ?QuayBollard $bollard = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'mooring_pier', referencedColumnName: 'pier')]
    #[ORM\JoinColumn(name: 'mooring_slot', referencedColumnName: 'slot')]
    public ?QuayMooring $mooring = null;
}
