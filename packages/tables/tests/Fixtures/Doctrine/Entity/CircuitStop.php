<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The collection a {@see Circuit} owns through a NOT NULL pair of join columns,
 * so a record of it can be listed and fetch-joined but never dissociated from
 * the record that owns it.
 */
#[ORM\Entity]
class CircuitStop
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\Column]
        private string $label,
        #[ORM\ManyToOne(targetEntity: Circuit::class, inversedBy: 'stops')]
        #[ORM\JoinColumn(name: 'circuit_region', referencedColumnName: 'region', nullable: false)]
        #[ORM\JoinColumn(name: 'circuit_slot', referencedColumnName: 'slot', nullable: false)]
        private Circuit $circuit
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCircuit(): Circuit
    {
        return $this->circuit;
    }
}
