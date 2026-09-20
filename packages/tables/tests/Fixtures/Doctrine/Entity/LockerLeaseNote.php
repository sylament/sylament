<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The to-many a {@see LockerLease} owns, reached back through the two join
 * columns its owner's two identifier associations write.
 */
#[ORM\Entity]
class LockerLeaseNote
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\Column]
        private string $heading,
        #[ORM\Column]
        private int $position,
        #[ORM\ManyToOne(targetEntity: LockerLease::class, inversedBy: 'notes')]
        #[ORM\JoinColumn(name: 'lease_locker_id', referencedColumnName: 'locker_id')]
        #[ORM\JoinColumn(name: 'lease_artist_id', referencedColumnName: 'artist_id')]
        private ?LockerLease $lease = null
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getHeading(): string
    {
        return $this->heading;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getLease(): ?LockerLease
    {
        return $this->lease;
    }
}
