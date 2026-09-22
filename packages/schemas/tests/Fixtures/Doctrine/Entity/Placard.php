<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * One identifier field, and it is a backed enum: the single-owner bind names
 * the field's own DBAL type and carries no unwrap of its own, because Doctrine
 * reads an enum-mapped identifier through `ReflectionEnumProperty`, which
 * already answers the backing value.
 */
#[ORM\Entity]
class Placard
{
    #[ORM\Id]
    #[ORM\Column(length: 16, enumType: Zone::class)]
    private ?Zone $zone = null;

    #[ORM\Column]
    private string $label = '';

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'placard_topic_link')]
    #[ORM\JoinColumn(name: 'placard_zone', referencedColumnName: 'zone')]
    #[ORM\InverseJoinColumn(name: 'topic_id', referencedColumnName: 'id')]
    private Collection $topics;

    /**
     * @var Collection<int, Slip>
     */
    #[ORM\OneToMany(targetEntity: Slip::class, mappedBy: 'placard')]
    private Collection $slips;

    public function __construct()
    {
        $this->topics = new ArrayCollection();
        $this->slips = new ArrayCollection();
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(Zone $zone): void
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
