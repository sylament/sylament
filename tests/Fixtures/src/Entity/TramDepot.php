<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An owner keyed by one generated integer whose one-to-manys reach a child of every shape a
 * one-column foreign key writes differently or keys differently: the leaf of a single-table
 * hierarchy ({@see TramDialGauge}), the leaf of a joined one whose root declares the foreign
 * key ({@see TramToaster}), and a composite-keyed child of each flavour, two scalars
 * ({@see TramCrate}), an association plus a scalar ({@see TramParcel}) and a decimal plus a
 * string that two spellings name ({@see TramTariff}).
 */
#[ORM\Entity]
class TramDepot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, TramDialGauge>
     */
    #[ORM\OneToMany(targetEntity: TramDialGauge::class, mappedBy: 'depot')]
    public Collection $dials;

    /**
     * @var Collection<int, TramToaster>
     */
    #[ORM\OneToMany(targetEntity: TramToaster::class, mappedBy: 'depot')]
    public Collection $toasters;

    /**
     * @var Collection<int, TramCrate>
     */
    #[ORM\OneToMany(targetEntity: TramCrate::class, mappedBy: 'depot')]
    public Collection $crates;

    /**
     * @var Collection<int, TramParcel>
     */
    #[ORM\OneToMany(targetEntity: TramParcel::class, mappedBy: 'depot')]
    public Collection $parcels;

    /**
     * @var Collection<int, TramTariff>
     */
    #[ORM\OneToMany(targetEntity: TramTariff::class, mappedBy: 'depot')]
    public Collection $tariffs;

    public function __construct(
        #[ORM\Column]
        public string $name = '',
    ) {
        $this->dials = new ArrayCollection();
        $this->toasters = new ArrayCollection();
        $this->crates = new ArrayCollection();
        $this->parcels = new ArrayCollection();
        $this->tariffs = new ArrayCollection();
    }
}
