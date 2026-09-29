<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The root of a class-table hierarchy keyed by a composite identifier.
 */
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'kind', type: 'string', length: 16)]
#[ORM\DiscriminatorMap(['vehicle' => Vehicle::class, 'truck' => Truck::class])]
class Vehicle
{
    #[ORM\Id]
    #[ORM\Column(length: 16)]
    private string $fleet = '';

    #[ORM\Id]
    #[ORM\Column]
    private int $number = 0;

    public function getFleet(): string
    {
        return $this->fleet;
    }

    public function setFleet(string $fleet): void
    {
        $this->fleet = $fleet;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
    }
}
