<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record keyed by the composite identifier its mapped superclass declares.
 */
#[ORM\Entity]
class Account extends TenantKeyed
{
    #[ORM\Column(length: 64)]
    private string $label = '';

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}
