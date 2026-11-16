<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A {@see Quay}'s record keyed by two strings the application assigns, blank until written,
 * constructible without arguments, whose prePersist callback stamps its label and fills no
 * key half.
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class QuayMooring
{
    #[ORM\Id]
    #[ORM\Column(length: 40)]
    public string $pier = '';

    #[ORM\Id]
    #[ORM\Column(length: 40)]
    public string $slot = '';

    #[ORM\Column]
    public string $label = '';

    #[ORM\PrePersist]
    public function stampLabel(): void
    {
        $this->label = "Moored: {$this->label}";
    }
}
