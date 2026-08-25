<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A value object embedded in `Tagged` — the replicator clones one rather than
 * sharing it, so a case can tell the two apart by object identity.
 */
#[ORM\Embeddable]
class Address
{
    public function __construct(
        #[ORM\Column(length: 255)]
        private string $street = ''
    ) {
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }
}
