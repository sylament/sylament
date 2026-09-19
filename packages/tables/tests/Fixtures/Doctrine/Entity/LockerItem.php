<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The to-many a {@see Locker} owns, so a root keyed by a converting type has a
 * collection to aggregate, search and fetch-join across.
 */
#[ORM\Entity]
class LockerItem
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\Column]
        private string $name,
        #[ORM\Column]
        private int $weight,
        #[ORM\ManyToOne(targetEntity: Locker::class, inversedBy: 'items')]
        #[ORM\JoinColumn(referencedColumnName: 'code')]
        private ?Locker $locker = null
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

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function getLocker(): ?Locker
    {
        return $this->locker;
    }
}
