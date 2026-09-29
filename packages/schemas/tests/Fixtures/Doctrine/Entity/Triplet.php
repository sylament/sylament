<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A composite identifier of three scalar fields.
 */
#[ORM\Entity]
class Triplet
{
    #[ORM\Id]
    #[ORM\Column(length: 16)]
    private string $region = '';

    #[ORM\Id]
    #[ORM\Column]
    private int $slot = 0;

    #[ORM\Id]
    #[ORM\Column(length: 16)]
    private string $code = '';

    #[ORM\Column(length: 64)]
    private string $label = '';

    public function getRegion(): string
    {
        return $this->region;
    }

    public function setRegion(string $region): void
    {
        $this->region = $region;
    }

    public function getSlot(): int
    {
        return $this->slot;
    }

    public function setSlot(int $slot): void
    {
        $this->slot = $slot;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
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
