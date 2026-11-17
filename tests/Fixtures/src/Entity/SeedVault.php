<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The owning side of a many-to-many that declares `orphanRemoval`, so a packet taken out
 * of {@see SeedVault::$packets} is deleted rather than unlinked; its adders do not call the
 * packet back.
 */
#[ORM\Entity]
class SeedVault
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, SeedPacket>
     */
    #[ORM\ManyToMany(targetEntity: SeedPacket::class, inversedBy: 'vaults', orphanRemoval: true)]
    public Collection $packets;

    public function __construct(
        #[ORM\Column]
        public string $name = '',
    ) {
        $this->packets = new ArrayCollection();
    }

    public function addPacket(SeedPacket $packet): void
    {
        $this->packets->add($packet);
    }

    public function removePacket(SeedPacket $packet): void
    {
        $this->packets->removeElement($packet);
    }
}
