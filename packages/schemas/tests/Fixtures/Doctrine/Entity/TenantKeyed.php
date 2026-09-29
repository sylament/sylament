<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A composite identifier declared on a mapped superclass.
 */
#[ORM\MappedSuperclass]
abstract class TenantKeyed
{
    #[ORM\Id]
    #[ORM\Column(length: 16)]
    private string $tenant = '';

    #[ORM\Id]
    #[ORM\Column(length: 16)]
    private string $code = '';

    public function getTenant(): string
    {
        return $this->tenant;
    }

    public function setTenant(string $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }
}
