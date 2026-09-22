<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    /**
     * The collections that make a composite identifier one half of which
     * converts a relationship owner, so each half is bound under its own DBAL
     * type rather than as it prints.
     *
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'listing_note_topic_link')]
    #[ORM\JoinColumn(name: 'note_listing_sku', referencedColumnName: 'listing_id')]
    #[ORM\JoinColumn(name: 'note_code', referencedColumnName: 'code')]
    #[ORM\InverseJoinColumn(name: 'topic_id', referencedColumnName: 'id')]
    private Collection $topics;

    /**
     * @var Collection<int, Slip>
     */
    #[ORM\OneToMany(targetEntity: Slip::class, mappedBy: 'note')]
    private Collection $slips;

    public function __construct()
    {
        $this->topics = new ArrayCollection();
        $this->slips = new ArrayCollection();
    }

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
