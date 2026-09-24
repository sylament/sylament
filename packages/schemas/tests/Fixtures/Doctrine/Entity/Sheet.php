<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A member of an inverse collection keyed by the record it hangs off plus a
 * code of its own — the association half first, so the field a refusal names
 * is not simply the first one.
 */
#[ORM\Entity]
class Sheet
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Binder::class, inversedBy: 'sheets')]
    #[ORM\JoinColumn(name: 'binder_id', referencedColumnName: 'id')]
    private ?Binder $binder = null;

    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $code = '';

    #[ORM\Column]
    private string $title = '';

    public function getBinder(): ?Binder
    {
        return $this->binder;
    }

    public function setBinder(?Binder $binder): void
    {
        $this->binder = $binder;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
