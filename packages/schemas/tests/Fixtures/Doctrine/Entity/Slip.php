<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The one-to-many arm of every owner flavour this package keys but
 * {@see Marker} does not carry: a converting single identifier, a composite one
 * with a converting half, a type that cannot read its printed form, a backed
 * enum, and a single-table subclass. None of these to-ones is part of this
 * record's identity.
 */
#[ORM\Entity]
class Slip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $label = '';

    #[ORM\ManyToOne(targetEntity: Listing::class, inversedBy: 'slips')]
    #[ORM\JoinColumn(name: 'listing_sku', referencedColumnName: 'sku')]
    private ?Listing $listing = null;

    #[ORM\ManyToOne(targetEntity: ListingNote::class, inversedBy: 'slips')]
    #[ORM\JoinColumn(name: 'note_listing_sku', referencedColumnName: 'listing_id')]
    #[ORM\JoinColumn(name: 'note_code', referencedColumnName: 'code')]
    private ?ListingNote $note = null;

    #[ORM\ManyToOne(targetEntity: Vault::class, inversedBy: 'slips')]
    #[ORM\JoinColumn(name: 'vault_cipher', referencedColumnName: 'cipher')]
    #[ORM\JoinColumn(name: 'vault_zone', referencedColumnName: 'zone')]
    private ?Vault $vault = null;

    #[ORM\ManyToOne(targetEntity: Placard::class, inversedBy: 'slips')]
    #[ORM\JoinColumn(name: 'placard_zone', referencedColumnName: 'zone')]
    private ?Placard $placard = null;

    #[ORM\ManyToOne(targetEntity: Sigil::class, inversedBy: 'slips')]
    #[ORM\JoinColumn(name: 'sigil_realm', referencedColumnName: 'realm')]
    #[ORM\JoinColumn(name: 'sigil_mark', referencedColumnName: 'mark')]
    private ?Sigil $sigil = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getListing(): ?Listing
    {
        return $this->listing;
    }

    public function setListing(?Listing $listing): void
    {
        $this->listing = $listing;
    }

    public function getNote(): ?ListingNote
    {
        return $this->note;
    }

    public function setNote(?ListingNote $note): void
    {
        $this->note = $note;
    }

    public function getVault(): ?Vault
    {
        return $this->vault;
    }

    public function setVault(?Vault $vault): void
    {
        $this->vault = $vault;
    }

    public function getPlacard(): ?Placard
    {
        return $this->placard;
    }

    public function setPlacard(?Placard $placard): void
    {
        $this->placard = $placard;
    }

    public function getSigil(): ?Sigil
    {
        return $this->sigil;
    }

    public function setSigil(?Sigil $sigil): void
    {
        $this->sigil = $sigil;
    }
}
