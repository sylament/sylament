<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A single derived identity onto a target stored as sixteen raw bytes: a key
 * of this class decodes only through the codec's writer fallback, and a
 * reference to one is loaded rather than built, since its identifier field is
 * an association.
 */
#[ORM\Entity]
class CrateSeal
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Crate::class)]
    #[ORM\JoinColumn(name: 'crate_id', referencedColumnName: 'id')]
    private ?Crate $crate = null;

    #[ORM\Column]
    private string $label = '';

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'crate_seal_topic_link')]
    #[ORM\JoinColumn(name: 'seal_crate_id', referencedColumnName: 'crate_id')]
    #[ORM\InverseJoinColumn(name: 'topic_id', referencedColumnName: 'id')]
    private Collection $topics;

    /**
     * @var Collection<int, Slip>
     */
    #[ORM\OneToMany(targetEntity: Slip::class, mappedBy: 'crateSeal')]
    private Collection $slips;

    public function __construct()
    {
        $this->topics = new ArrayCollection();
        $this->slips = new ArrayCollection();
    }

    public function getCrate(): ?Crate
    {
        return $this->crate;
    }

    public function setCrate(Crate $crate): void
    {
        $this->crate = $crate;
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
