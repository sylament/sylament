<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single derived identity onto a spine, whose own key is a string — so the
 * headband's key is blank for as long as its spine's is, though the field
 * holding it is an object.
 */
#[ORM\Entity]
class Headband
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Spine::class)]
    #[ORM\JoinColumn(name: 'spine_code', referencedColumnName: 'code')]
    private ?Spine $spine = null;

    public function getSpine(): ?Spine
    {
        return $this->spine;
    }

    public function setSpine(?Spine $spine): void
    {
        $this->spine = $spine;
    }
}
