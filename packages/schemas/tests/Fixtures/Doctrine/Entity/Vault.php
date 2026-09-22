<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Cipher;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CipherType;

/**
 * A scalar pair one half of which is typed by a DBAL type that cannot read its
 * own printed form, so the key this record mints never decodes against its own
 * mapping — the one owner flavour the reader refuses by name rather than
 * scoping.
 */
#[ORM\Entity]
class Vault
{
    #[ORM\Id]
    #[ORM\Column(type: CipherType::NAME)]
    private ?Cipher $cipher = null;

    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $zone = '';

    #[ORM\Column]
    private string $label = '';

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'vault_topic_link')]
    #[ORM\JoinColumn(name: 'vault_cipher', referencedColumnName: 'cipher')]
    #[ORM\JoinColumn(name: 'vault_zone', referencedColumnName: 'zone')]
    #[ORM\InverseJoinColumn(name: 'topic_id', referencedColumnName: 'id')]
    private Collection $topics;

    /**
     * @var Collection<int, Slip>
     */
    #[ORM\OneToMany(targetEntity: Slip::class, mappedBy: 'vault')]
    private Collection $slips;

    public function __construct()
    {
        $this->topics = new ArrayCollection();
        $this->slips = new ArrayCollection();
    }

    public function getCipher(): ?Cipher
    {
        return $this->cipher;
    }

    public function setCipher(Cipher $cipher): void
    {
        $this->cipher = $cipher;
    }

    public function getZone(): string
    {
        return $this->zone;
    }

    public function setZone(string $zone): void
    {
        $this->zone = $zone;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTopics(): Collection
    {
        return $this->topics;
    }

    public function addTopic(Tag $topic): void
    {
        if (!$this->topics->contains($topic)) {
            $this->topics->add($topic);
        }
    }

    /**
     * @return Collection<int, Slip>
     */
    public function getSlips(): Collection
    {
        return $this->slips;
    }
}
