<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single derived identity beside a plain column that happens to be called
 * `id`: one identifier field, readable and non-blank, and an association
 * rather than a scalar — so only the shape of the identifier decides.
 */
#[ORM\Entity]
class Manifest
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Roster::class)]
    private ?Roster $roster = null;

    #[ORM\Column]
    private int $id = 0;

    #[ORM\Column(length: 255)]
    private string $carrier = '';

    public function getRoster(): ?Roster
    {
        return $this->roster;
    }

    public function setRoster(?Roster $roster): void
    {
        $this->roster = $roster;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCarrier(): string
    {
        return $this->carrier;
    }

    public function setCarrier(string $carrier): void
    {
        $this->carrier = $carrier;
    }
}
