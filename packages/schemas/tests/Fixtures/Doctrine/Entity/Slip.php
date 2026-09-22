<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The one-to-many arm of every owner flavour this package keys but
 * {@see Marker} does not carry: a converting single identifier, a composite one
 * with a converting half, a doubly-strict type that refuses its printed form on
 * read and on write, a backed enum, a single-table subclass, a single and a
 * composite identifier stored as raw bytes, and three single derived
 * identities — onto a converting, a raw-bytes and a doubly-strict target. None
 * of these to-ones is part of this record's identity.
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

    #[ORM\ManyToOne(targetEntity: Crate::class, inversedBy: 'slips')]
    #[ORM\JoinColumn(name: 'crate_id', referencedColumnName: 'id')]
    private ?Crate $crate = null;

    #[ORM\ManyToOne(targetEntity: Pallet::class, inversedBy: 'slips')]
    #[ORM\JoinColumn(name: 'pallet_batch', referencedColumnName: 'batch')]
    #[ORM\JoinColumn(name: 'pallet_lane', referencedColumnName: 'lane')]
    private ?Pallet $pallet = null;

    #[ORM\ManyToOne(targetEntity: ListingSeal::class, inversedBy: 'slips')]
    #[ORM\JoinColumn(name: 'listing_seal_sku', referencedColumnName: 'listing_sku')]
    private ?ListingSeal $listingSeal = null;

    #[ORM\ManyToOne(targetEntity: CrateSeal::class, inversedBy: 'slips')]
    #[ORM\JoinColumn(name: 'crate_seal_id', referencedColumnName: 'crate_id')]
    private ?CrateSeal $crateSeal = null;

    #[ORM\ManyToOne(targetEntity: StrongboxSeal::class, inversedBy: 'slips')]
    #[ORM\JoinColumn(name: 'strongbox_seal_cipher', referencedColumnName: 'strongbox_cipher')]
    private ?StrongboxSeal $strongboxSeal = null;

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

    public function getCrate(): ?Crate
    {
        return $this->crate;
    }

    public function setCrate(?Crate $crate): void
    {
        $this->crate = $crate;
    }

    public function getPallet(): ?Pallet
    {
        return $this->pallet;
    }

    public function setPallet(?Pallet $pallet): void
    {
        $this->pallet = $pallet;
    }

    public function getListingSeal(): ?ListingSeal
    {
        return $this->listingSeal;
    }

    public function setListingSeal(?ListingSeal $listingSeal): void
    {
        $this->listingSeal = $listingSeal;
    }

    public function getCrateSeal(): ?CrateSeal
    {
        return $this->crateSeal;
    }

    public function setCrateSeal(?CrateSeal $crateSeal): void
    {
        $this->crateSeal = $crateSeal;
    }

    public function getStrongboxSeal(): ?StrongboxSeal
    {
        return $this->strongboxSeal;
    }

    public function setStrongboxSeal(?StrongboxSeal $strongboxSeal): void
    {
        $this->strongboxSeal = $strongboxSeal;
    }
}
