<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A {@see Residency}'s re-pointable child, so the derived-identity flavour owns
 * a collection whose records can be associated and dissociated — which the
 * constructor-promoted {@see ResidencyShow} beside it cannot.
 */
#[ORM\Entity]
class ResidencyRider
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\Column]
        private string $name,
        #[ORM\Column]
        private int $position,
        #[ORM\ManyToOne(targetEntity: Residency::class, inversedBy: 'riders')]
        #[ORM\JoinColumn(name: 'residency_venue_id', referencedColumnName: 'venue_id')]
        #[ORM\JoinColumn(name: 'residency_season', referencedColumnName: 'season')]
        private ?Residency $residency = null
    ) {
    }

    public function getId(): int
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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getResidency(): ?Residency
    {
        return $this->residency;
    }

    public function setResidency(?Residency $residency): void
    {
        $this->residency = $residency;
    }
}
