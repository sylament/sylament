<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The child of every owner's ordinary one-to-many, pointing at each kind of owner: one
 * keyed by a generated integer, by two scalars, by an association and a scalar, by a
 * uuid, and by an identity derived from that uuid.
 */
#[ORM\Entity]
class Seedling
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\ManyToOne(inversedBy: 'seedlings')]
    public ?Garden $garden = null;

    #[ORM\ManyToOne(inversedBy: 'seedlings')]
    #[ORM\JoinColumn(name: 'terrace_estate', referencedColumnName: 'estate')]
    #[ORM\JoinColumn(name: 'terrace_number', referencedColumnName: 'number')]
    public ?Terrace $terrace = null;

    #[ORM\ManyToOne(inversedBy: 'seedlings')]
    #[ORM\JoinColumn(name: 'allotment_garden', referencedColumnName: 'garden_id')]
    #[ORM\JoinColumn(name: 'allotment_number', referencedColumnName: 'number')]
    public ?Allotment $allotment = null;

    #[ORM\ManyToOne(inversedBy: 'seedlings')]
    public ?Greenhouse $greenhouse = null;

    #[ORM\ManyToOne(inversedBy: 'seedlings')]
    #[ORM\JoinColumn(name: 'vent', referencedColumnName: 'greenhouse_id')]
    public ?GreenhouseVent $vent = null;

    public function __construct(
        #[ORM\Column]
        public string $name = '',
    ) {
    }
}
