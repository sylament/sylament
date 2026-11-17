<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The inverse side of {@see SeedVault::$packets}, whose owning collection deletes the
 * packet it drops; its adders do not call the vault back.
 */
#[ORM\Entity]
class SeedPacket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, SeedVault>
     */
    #[ORM\ManyToMany(targetEntity: SeedVault::class, mappedBy: 'packets')]
    public Collection $vaults;

    public function __construct(
        #[ORM\Column]
        public string $name = '',
    ) {
        $this->vaults = new ArrayCollection();
    }

    public function addVault(SeedVault $vault): void
    {
        $this->vaults->add($vault);
    }

    public function removeVault(SeedVault $vault): void
    {
        $this->vaults->removeElement($vault);
    }
}
