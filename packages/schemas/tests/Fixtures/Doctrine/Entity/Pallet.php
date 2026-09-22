<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylament\Schemas\Tests\Fixtures\Doctrine\BinaryUuidType;
use Symfony\Component\Uid\Uuid;

/**
 * A scalar pair one half of which is stored as sixteen raw bytes — a composite
 * owner whose key decodes only because the codec reads that half back through
 * its type's writer. Both halves are scalar, so a reference to one is built
 * from the decoded values rather than loaded.
 */
#[ORM\Entity]
class Pallet
{
    #[ORM\Id]
    #[ORM\Column(type: BinaryUuidType::NAME)]
    private ?Uuid $batch = null;

    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $lane = '';

    #[ORM\Column]
    private string $label = '';

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'pallet_topic_link')]
    #[ORM\JoinColumn(name: 'pallet_batch', referencedColumnName: 'batch')]
    #[ORM\JoinColumn(name: 'pallet_lane', referencedColumnName: 'lane')]
    #[ORM\InverseJoinColumn(name: 'topic_id', referencedColumnName: 'id')]
    private Collection $topics;

    /**
     * @var Collection<int, Slip>
     */
    #[ORM\OneToMany(targetEntity: Slip::class, mappedBy: 'pallet')]
    private Collection $slips;

    public function __construct()
    {
        $this->topics = new ArrayCollection();
        $this->slips = new ArrayCollection();
    }

    public function getBatch(): ?Uuid
    {
        return $this->batch;
    }

    public function setBatch(Uuid $batch): void
    {
        $this->batch = $batch;
    }

    public function getLane(): string
    {
        return $this->lane;
    }

    public function setLane(string $lane): void
    {
        $this->lane = $lane;
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
