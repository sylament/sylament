<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A chained derived identity: the identifier association targets a class whose
 * own identifier is an association too. It holds a many-to-many and
 * deliberately no one-to-many — Doctrine fatals hydrating any child of such an
 * owner against a cold identity map, so a back-reference here would poison
 * every cold read of the child class rather than this one.
 */
#[ORM\Entity]
class Chain
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: PostSeal::class)]
    #[ORM\JoinColumn(name: 'seal_post_id', referencedColumnName: 'post_id')]
    private ?PostSeal $seal = null;

    #[ORM\Id]
    #[ORM\Column]
    private int $link = 0;

    #[ORM\Column]
    private string $label = '';

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'chain_topic_link')]
    #[ORM\JoinColumn(name: 'chain_seal_post_id', referencedColumnName: 'seal_post_id')]
    #[ORM\JoinColumn(name: 'chain_link', referencedColumnName: 'link')]
    #[ORM\InverseJoinColumn(name: 'topic_id', referencedColumnName: 'id')]
    private Collection $topics;

    public function __construct()
    {
        $this->topics = new ArrayCollection();
    }

    public function getSeal(): ?PostSeal
    {
        return $this->seal;
    }

    public function setSeal(PostSeal $seal): void
    {
        $this->seal = $seal;
    }

    public function getLink(): int
    {
        return $this->link;
    }

    public function setLink(int $link): void
    {
        $this->link = $link;
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
}
