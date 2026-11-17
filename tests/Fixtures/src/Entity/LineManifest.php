<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An owner keyed by one generated integer whose many-to-manys, each owned here and mapped
 * one way, reach a composite-keyed target of every flavour: two scalar fields
 * ({@see OrderLine}), an association plus a scalar ({@see InvoiceLine}), and a decimal plus a
 * string, whose decimal half two spellings name ({@see TariffBand}).
 */
#[ORM\Entity]
class LineManifest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    /**
     * @var Collection<int, OrderLine>
     */
    #[ORM\ManyToMany(targetEntity: OrderLine::class)]
    #[ORM\JoinTable(name: 'line_manifest_order_line')]
    #[ORM\JoinColumn(name: 'manifest_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'order_number', referencedColumnName: 'order_number')]
    #[ORM\InverseJoinColumn(name: 'line_number', referencedColumnName: 'line_number')]
    public Collection $orderLines;

    /**
     * @var Collection<int, InvoiceLine>
     */
    #[ORM\ManyToMany(targetEntity: InvoiceLine::class)]
    #[ORM\JoinTable(name: 'line_manifest_invoice_line')]
    #[ORM\JoinColumn(name: 'manifest_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'invoice_id', referencedColumnName: 'invoice_id')]
    #[ORM\InverseJoinColumn(name: 'line', referencedColumnName: 'line')]
    public Collection $invoiceLines;

    /**
     * @var Collection<int, TariffBand>
     */
    #[ORM\ManyToMany(targetEntity: TariffBand::class)]
    #[ORM\JoinTable(name: 'line_manifest_tariff_band')]
    #[ORM\JoinColumn(name: 'manifest_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'band_rate', referencedColumnName: 'rate')]
    #[ORM\InverseJoinColumn(name: 'band_zone', referencedColumnName: 'zone')]
    public Collection $tariffBands;

    public function __construct()
    {
        $this->orderLines = new ArrayCollection();
        $this->invoiceLines = new ArrayCollection();
        $this->tariffBands = new ArrayCollection();
    }

    public function addOrderLine(OrderLine $orderLine): void
    {
        $this->orderLines->add($orderLine);
    }

    public function removeOrderLine(OrderLine $orderLine): void
    {
        $this->orderLines->removeElement($orderLine);
    }

    public function addInvoiceLine(InvoiceLine $invoiceLine): void
    {
        $this->invoiceLines->add($invoiceLine);
    }

    public function removeInvoiceLine(InvoiceLine $invoiceLine): void
    {
        $this->invoiceLines->removeElement($invoiceLine);
    }

    public function addTariffBand(TariffBand $tariffBand): void
    {
        $this->tariffBands->add($tariffBand);
    }

    public function removeTariffBand(TariffBand $tariffBand): void
    {
        $this->tariffBands->removeElement($tariffBand);
    }
}
