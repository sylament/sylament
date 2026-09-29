<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A composite identifier with an int-backed enum half.
 */
#[ORM\Entity]
class Bay
{
    #[ORM\Id]
    #[ORM\Column(enumType: Tier::class)]
    private Tier $tier = Tier::Low;

    #[ORM\Id]
    #[ORM\Column]
    private int $slot = 0;

    #[ORM\Column(length: 64)]
    private string $label = '';

    public function getTier(): Tier
    {
        return $this->tier;
    }

    public function setTier(Tier $tier): void
    {
        $this->tier = $tier;
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
