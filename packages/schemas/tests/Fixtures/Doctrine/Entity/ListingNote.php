<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A derived identity onto a target keyed by a converting custom type.
 */
#[ORM\Entity]
class ListingNote
{
    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(referencedColumnName: 'sku')]
    private ?Listing $listing = null;

    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $code = '';

    #[ORM\Column]
    private string $body = '';

    public function getListing(): ?Listing
    {
        return $this->listing;
    }

    public function setListing(?Listing $listing): void
    {
        $this->listing = $listing;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }
}
