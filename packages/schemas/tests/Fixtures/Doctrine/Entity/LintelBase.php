<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A generated-id record holding the inverse side of a shared-key one-to-one,
 * whose setter syncs the owning side as Symfony's maker writes it — so the
 * cap's identity is filled by the write that hands the cap to the base.
 */
#[ORM\Entity]
class LintelBase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $name = '';

    #[ORM\OneToOne(targetEntity: LintelCap::class, mappedBy: 'base', cascade: ['persist'])]
    private ?LintelCap $cap = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCap(): ?LintelCap
    {
        return $this->cap;
    }

    public function setCap(?LintelCap $cap): void
    {
        if ($cap instanceof LintelCap && $cap->getBase() !== $this) {
            $cap->setBase($this);
        }

        $this->cap = $cap;
    }
}
