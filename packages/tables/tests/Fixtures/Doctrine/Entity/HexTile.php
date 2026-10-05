<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylament\Tables\Tests\Fixtures\Doctrine\HexCodeType;

/**
 * A record keyed by a {@see HexCodeType} field and a plain string, so the
 * non-idempotent reader meets a composite key.
 */
#[ORM\Entity]
class HexTile
{
    #[ORM\ManyToOne(targetEntity: HexOwner::class, inversedBy: 'keptTiles')]
    private ?HexOwner $keeper = null;

    /**
     * @var Collection<int, HexOwner>
     */
    #[ORM\ManyToMany(targetEntity: HexOwner::class, mappedBy: 'tiles')]
    private Collection $owners;

    /**
     * @var Collection<int, HexDot>
     */
    #[ORM\OneToMany(targetEntity: HexDot::class, mappedBy: 'tile')]
    private Collection $dots;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: HexCodeType::NAME)]
        private string $code,
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        private string $zone,
        #[ORM\Column]
        private string $label,
    ) {
        $this->owners = new ArrayCollection();
        $this->dots = new ArrayCollection();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getZone(): string
    {
        return $this->zone;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getKeeper(): ?HexOwner
    {
        return $this->keeper;
    }

    public function setKeeper(?HexOwner $keeper): void
    {
        $this->keeper = $keeper;
    }

    /**
     * @return Collection<int, HexOwner>
     */
    public function getOwners(): Collection
    {
        return $this->owners;
    }

    /**
     * @return Collection<int, HexDot>
     */
    public function getDots(): Collection
    {
        return $this->dots;
    }
}
