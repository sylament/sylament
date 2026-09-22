<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The root of a single-table hierarchy, and a record class of its own: a plain
 * gauge shares the table and the foreign key with the {@see Dial} a
 * {@see Depot} collects, so only the discriminator tells the two rows apart.
 */
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'kind', type: 'string')]
#[ORM\DiscriminatorMap(['gauge' => Gauge::class, 'dial' => Dial::class])]
class Gauge
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\Column]
        private string $label,
        #[ORM\ManyToOne(targetEntity: Depot::class, inversedBy: 'dials')]
        private ?Depot $depot = null,
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

    public function getDepot(): ?Depot
    {
        return $this->depot;
    }

    public function setDepot(?Depot $depot): void
    {
        $this->depot = $depot;
    }
}
