<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single string identifier the class fills from its own prePersist callback
 * when it is still blank, so two new slates both carry `''` until the flush
 * keys them apart.
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Slate
{
    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $code = '';

    #[ORM\ManyToOne(targetEntity: Binder::class, inversedBy: 'slates')]
    #[ORM\JoinColumn(name: 'binder_id', referencedColumnName: 'id')]
    private ?Binder $binder = null;

    #[ORM\Column]
    private string $label = '';

    #[ORM\PrePersist]
    public function assignCode(): void
    {
        if ($this->code === '') {
            $this->code = bin2hex(random_bytes(4));
        }
    }

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

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}
