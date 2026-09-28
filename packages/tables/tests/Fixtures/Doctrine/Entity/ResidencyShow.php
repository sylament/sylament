<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The to-many a {@see Residency} owns, so the derived-identity flavour reaches
 * a collection of its own.
 */
#[ORM\Entity]
class ResidencyShow
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\Column]
        private string $title,
        #[ORM\Column]
        private int $seats,
        #[ORM\ManyToOne(targetEntity: Residency::class, inversedBy: 'shows')]
        #[ORM\JoinColumn(name: 'residency_venue_id', referencedColumnName: 'venue_id', onDelete: 'CASCADE')]
        #[ORM\JoinColumn(name: 'residency_season', referencedColumnName: 'season', onDelete: 'CASCADE')]
        private ?Residency $residency = null
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSeats(): int
    {
        return $this->seats;
    }

    public function getResidency(): ?Residency
    {
        return $this->residency;
    }
}
