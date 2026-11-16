<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by a scope the application assigns, null until a form writes it, and a
 * stamp a prePersist callback fills when it is still blank.
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class StampedBulletin
{
    #[ORM\Id]
    #[ORM\Column(length: 32)]
    public ?string $scope = null;

    #[ORM\Id]
    #[ORM\Column(length: 32)]
    public string $stamp = '';

    #[ORM\Column]
    public string $label = '';

    #[ORM\PrePersist]
    public function assignStamp(): void
    {
        if ($this->stamp === '') {
            $this->stamp = 'auto';
        }
    }
}
