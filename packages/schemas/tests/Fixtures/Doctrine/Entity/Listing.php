<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Sku;
use Sylament\Schemas\Tests\Fixtures\Doctrine\SkuType;

/**
 * A single identifier of a converting, strict custom type.
 */
#[ORM\Entity]
class Listing
{
    #[ORM\Id]
    #[ORM\Column(type: SkuType::NAME)]
    private ?Sku $sku = null;

    #[ORM\Column]
    private string $title = '';

    public function getSku(): ?Sku
    {
        return $this->sku;
    }

    public function setSku(Sku $sku): void
    {
        $this->sku = $sku;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
