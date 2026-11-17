<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The root of a joined hierarchy, which declares the {@see TramDepot} its
 * {@see TramToaster} leaf belongs to, so the foreign key sits on the root's table.
 */
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'kind', type: 'string', length: 16)]
#[ORM\DiscriminatorMap(['appliance' => TramAppliance::class, 'toaster' => TramToaster::class])]
class TramAppliance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\ManyToOne(inversedBy: 'toasters')]
    public ?TramDepot $depot = null;

    public function __construct(
        #[ORM\Column]
        public string $name = '',
    ) {
    }
}
