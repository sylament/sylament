<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record whose whole identifier is one association — `isIdentifierComposite`
 * is false and `containsForeignIdentifier` is true — so a read that tells the
 * two flavours apart by the composite flag alone misses this one.
 */
#[ORM\Entity]
class LockerSeal
{
    public function __construct(
        #[ORM\Id]
        #[ORM\OneToOne(targetEntity: Locker::class)]
        #[ORM\JoinColumn(referencedColumnName: 'code')]
        private Locker $locker,
        #[ORM\Column]
        private string $note,
        #[ORM\Column]
        private int $position
    ) {
    }

    public function getLocker(): Locker
    {
        return $this->locker;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
