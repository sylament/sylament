<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A single derived identity onto a doubly-strict target: its key never
 * decodes, yet it scopes the way a single scalar owner of that type does,
 * because it is bound by its target's identifier value rather than by its key.
 */
#[ORM\Entity]
class StrongboxSeal
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Strongbox::class)]
    #[ORM\JoinColumn(name: 'strongbox_cipher', referencedColumnName: 'cipher')]
    private ?Strongbox $strongbox = null;

    #[ORM\Column]
    private string $label = '';

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'strongbox_seal_topic_link')]
    #[ORM\JoinColumn(name: 'seal_strongbox_cipher', referencedColumnName: 'strongbox_cipher')]
    #[ORM\InverseJoinColumn(name: 'topic_id', referencedColumnName: 'id')]
    private Collection $topics;

    /**
     * @var Collection<int, Slip>
     */
    #[ORM\OneToMany(targetEntity: Slip::class, mappedBy: 'strongboxSeal')]
    private Collection $slips;

    public function __construct()
    {
        $this->topics = new ArrayCollection();
        $this->slips = new ArrayCollection();
    }

    public function getStrongbox(): ?Strongbox
    {
        return $this->strongbox;
    }

    public function setStrongbox(Strongbox $strongbox): void
    {
        $this->strongbox = $strongbox;
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
