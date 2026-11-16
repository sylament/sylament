<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A child of {@see Garden::$labels} whose join column is nullable but whose property does
 * not accept null.
 */
#[ORM\Entity]
class GardenLabel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'labels')]
        public Garden $garden,
    ) {
    }
}
