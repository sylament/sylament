<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An owner keyed by one assigned string the application leaves optional,
 * holding the inverse side of a shared-key one-to-one whose setter syncs the
 * owning side — so the top's whole identifier is this owner.
 */
#[ORM\Entity]
class TrestleOwner
{
    #[ORM\Id]
    #[ORM\Column(length: 100)]
    private ?string $code = null;

    #[ORM\OneToOne(targetEntity: TrestleTop::class, mappedBy: 'owner', cascade: ['persist'])]
    private ?TrestleTop $top = null;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getTop(): ?TrestleTop
    {
        return $this->top;
    }

    public function setTop(?TrestleTop $top): void
    {
        if ($top instanceof TrestleTop && $top->getOwner() !== $this) {
            $top->setOwner($this);
        }

        $this->top = $top;
    }
}
