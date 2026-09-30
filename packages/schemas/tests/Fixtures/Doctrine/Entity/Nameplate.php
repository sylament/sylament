<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Moniker;
use Sylament\Schemas\Tests\Fixtures\Doctrine\MonikerType;

/**
 * A value-object key half that starts blank, beside an integer half.
 */
#[ORM\Entity]
class Nameplate
{
    #[ORM\Id]
    #[ORM\Column(type: MonikerType::NAME, length: 64)]
    private Moniker $moniker;

    #[ORM\Id]
    #[ORM\Column]
    private int $slot = 0;

    #[ORM\Column(length: 64)]
    private string $label = '';

    public function __construct()
    {
        $this->moniker = new Moniker('');
    }

    public function getMoniker(): Moniker
    {
        return $this->moniker;
    }

    public function setMoniker(Moniker $moniker): void
    {
        $this->moniker = $moniker;
    }

    public function getSlot(): int
    {
        return $this->slot;
    }

    public function setSlot(int $slot): void
    {
        $this->slot = $slot;
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
