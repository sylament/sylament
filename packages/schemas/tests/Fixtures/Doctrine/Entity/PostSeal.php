<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A single derived identity — the one identifier field is a to-one
 * association — which Doctrine does not report as composite.
 */
#[ORM\Entity]
class PostSeal
{
    #[ORM\Id]
    #[ORM\OneToOne]
    private ?Post $post = null;

    #[ORM\Column]
    private string $note = '';

    /**
     * The collections that make a single derived identity a relationship
     * owner — the flavour Doctrine binds as a parameter, so these answer where
     * the three composite ones refuse.
     *
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'seal_topic_link')]
    #[ORM\JoinColumn(name: 'seal_post_id', referencedColumnName: 'post_id')]
    #[ORM\InverseJoinColumn(name: 'topic_id', referencedColumnName: 'id')]
    private Collection $topics;

    /**
     * @var Collection<int, Marker>
     */
    #[ORM\OneToMany(targetEntity: Marker::class, mappedBy: 'seal')]
    private Collection $markers;

    public function __construct()
    {
        $this->topics = new ArrayCollection();
        $this->markers = new ArrayCollection();
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): void
    {
        $this->post = $post;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
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
        if (! $this->topics->contains($topic)) {
            $this->topics->add($topic);
        }
    }

    public function removeTopic(Tag $topic): void
    {
        $this->topics->removeElement($topic);
    }

    /**
     * @return Collection<int, Marker>
     */
    public function getMarkers(): Collection
    {
        return $this->markers;
    }
}
