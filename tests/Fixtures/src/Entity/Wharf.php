<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An owner keyed by a single string the application assigns, nullable so no field over
 * it derives a requirement, holding the inverse side of a one-to-one whose record is
 * keyed by this wharf alone; the setter keeps the owning side in step.
 */
#[ORM\Entity]
class Wharf
{
    #[ORM\Id]
    #[ORM\Column]
    public ?string $code = null;

    #[ORM\OneToOne(mappedBy: 'wharf', cascade: ['persist'])]
    private ?WharfCrane $crane = null;

    public function getCrane(): ?WharfCrane
    {
        return $this->crane;
    }

    public function setCrane(?WharfCrane $crane): void
    {
        if ($crane instanceof WharfCrane) {
            $crane->wharf = $this;
        }

        $this->crane = $crane;
    }
}
