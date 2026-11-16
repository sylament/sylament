<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A child of {@see Garden::$plots}, which removes its orphans.
 */
#[ORM\Entity]
class GardenPlot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\ManyToOne(inversedBy: 'plots')]
    public ?Garden $garden = null;
}
