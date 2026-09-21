<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * One scalar identifier under a name of its own, with the convenience `id`
 * accessor a legacy-named key usually carries beside it — so the property a
 * mounted action reads spells the record's whole identity after all.
 */
#[ORM\Entity]
class Parcel
{
    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private string $sku = '';

    #[ORM\Column(length: 255)]
    private string $consignee = '';

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    public function getId(): string
    {
        return $this->sku;
    }

    public function getConsignee(): string
    {
        return $this->consignee;
    }

    public function setConsignee(string $consignee): void
    {
        $this->consignee = $consignee;
    }
}
