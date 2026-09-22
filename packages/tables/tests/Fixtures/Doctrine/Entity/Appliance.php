<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The root of a joined hierarchy, declaring the to-one its {@see Toaster} leaf
 * inherits — so the foreign key lives in this root's table, not in the leaf's,
 * and the identifier column is named apart from its field.
 */
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'kind', type: 'string')]
#[ORM\DiscriminatorMap(['toaster' => Toaster::class])]
abstract class Appliance
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(name: 'appliance_id')]
        private int $id,
        #[ORM\Column]
        private string $label,
        #[ORM\ManyToOne(targetEntity: Depot::class, inversedBy: 'toasters')]
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
