<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CaselessType;

/**
 * A member of an inverse collection keyed by a single code its column stores
 * lower-cased, so two new dividers typed `AB` and `ab` hold different strings
 * that the flush writes as one key.
 */
#[ORM\Entity]
class Divider
{
    #[ORM\Id]
    #[ORM\Column(type: CaselessType::NAME, length: 32)]
    private string $code = '';

    #[ORM\ManyToOne(targetEntity: Binder::class, inversedBy: 'dividers')]
    #[ORM\JoinColumn(name: 'binder_id', referencedColumnName: 'id')]
    private ?Binder $binder = null;

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getBinder(): ?Binder
    {
        return $this->binder;
    }

    public function setBinder(?Binder $binder): void
    {
        $this->binder = $binder;
    }
}
