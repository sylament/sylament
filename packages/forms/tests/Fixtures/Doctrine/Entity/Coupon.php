<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single assigned string identifier the class draws in its own prePersist
 * callback, blank until then — a key no create form shows.
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Coupon
{
    #[ORM\Id]
    #[ORM\Column(length: 16)]
    private string $code = '';

    #[ORM\ManyToOne(targetEntity: Raffle::class, inversedBy: 'coupons')]
    private ?Raffle $raffle = null;

    #[ORM\Column(length: 40)]
    private string $label = '';

    #[ORM\PrePersist]
    public function drawCode(): void
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

    public function getRaffle(): ?Raffle
    {
        return $this->raffle;
    }

    public function setRaffle(?Raffle $raffle): void
    {
        $this->raffle = $raffle;
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
