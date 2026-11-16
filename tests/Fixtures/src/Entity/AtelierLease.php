<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The owning side of an {@see Atelier}'s one-to-one lease.
 */
#[ORM\Entity]
class AtelierLease
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    public function __construct(
        #[ORM\OneToOne(inversedBy: 'lease')]
        public ?Atelier $atelier = null,
    ) {
    }
}
