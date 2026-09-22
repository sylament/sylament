<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A scalar pair at the root of a single-table hierarchy, with both collections
 * declared here rather than on the subclass that owns records — so a scope
 * built from a subclass owner carries the discriminator Doctrine adds itself.
 */
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'kind', type: 'string')]
#[ORM\DiscriminatorMap(['sigil' => Sigil::class, 'child' => SigilChild::class])]
class Sigil
{
    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $realm = '';

    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $mark = '';

    #[ORM\Column]
    private string $label = '';

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'sigil_topic_link')]
    #[ORM\JoinColumn(name: 'sigil_realm', referencedColumnName: 'realm')]
    #[ORM\JoinColumn(name: 'sigil_mark', referencedColumnName: 'mark')]
    #[ORM\InverseJoinColumn(name: 'topic_id', referencedColumnName: 'id')]
    private Collection $topics;

    /**
     * @var Collection<int, Slip>
     */
    #[ORM\OneToMany(targetEntity: Slip::class, mappedBy: 'sigil')]
    private Collection $slips;

    public function __construct()
    {
        $this->topics = new ArrayCollection();
        $this->slips = new ArrayCollection();
    }

    public function getRealm(): string
    {
        return $this->realm;
    }

    public function setRealm(string $realm): void
    {
        $this->realm = $realm;
    }

    public function getMark(): string
    {
        return $this->mark;
    }

    public function setMark(string $mark): void
    {
        $this->mark = $mark;
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
