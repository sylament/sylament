<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylament\Schemas\Tests\Fixtures\Doctrine\BinaryUuidType;
use Symfony\Component\Uid\Uuid;

/**
 * A single identifier stored as sixteen raw bytes, whose type reads only that
 * database form and so cannot read back the printed form its key carries.
 */
#[ORM\Entity]
class Crate
{
    #[ORM\Id]
    #[ORM\Column(type: BinaryUuidType::NAME)]
    private ?Uuid $id = null;

    #[ORM\Column]
    private string $label = '';

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'crate_topic_link')]
    #[ORM\JoinColumn(name: 'crate_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'topic_id', referencedColumnName: 'id')]
    private Collection $topics;

    /**
     * @var Collection<int, Slip>
     */
    #[ORM\OneToMany(targetEntity: Slip::class, mappedBy: 'crate')]
    private Collection $slips;

    public function __construct()
    {
        $this->topics = new ArrayCollection();
        $this->slips = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): void
    {
        $this->id = $id;
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
