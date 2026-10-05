<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A derived identity whose constructor sets both halves — the {@see Mill} it
 * nests under and its initials — and no setter can change either, so a nested
 * create page builds it in makeRecord() and each attach to the page's parent
 * meets a parent half already held.
 */
#[ORM\Entity]
class Countermark
{
    #[ORM\Column(length: 255)]
    private string $caption = '';

    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Mill::class, inversedBy: 'countermarks')]
        private readonly Mill $mill,
        #[ORM\Id]
        #[ORM\Column(length: 40)]
        private readonly string $initials,
    ) {
    }

    public function getMill(): Mill
    {
        return $this->mill;
    }

    public function getInitials(): string
    {
        return $this->initials;
    }

    public function getCaption(): string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): static
    {
        $this->caption = (string) $caption;

        return $this;
    }
}
