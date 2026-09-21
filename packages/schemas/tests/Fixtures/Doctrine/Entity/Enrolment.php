<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A join entity: both identifier fields are to-one associations, so nothing of
 * its own identity is a column a caller could name.
 */
#[ORM\Entity]
class Enrolment
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Post::class)]
    private ?Post $post = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Tag::class)]
    private ?Tag $tag = null;

    #[ORM\Column]
    private string $note = '';

    /**
     * The collection that makes a join entity a relationship owner, so the
     * owner guard has one of this flavour to refuse.
     *
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'enrolment_topic_link')]
    #[ORM\JoinColumn(name: 'enrolment_post_id', referencedColumnName: 'post_id')]
    #[ORM\JoinColumn(name: 'enrolment_tag_id', referencedColumnName: 'tag_id')]
    #[ORM\InverseJoinColumn(name: 'topic_id', referencedColumnName: 'id')]
    private Collection $topics;

    /**
     * @var Collection<int, Marker>
     */
    #[ORM\OneToMany(targetEntity: Marker::class, mappedBy: 'enrolment')]
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

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(?Tag $tag): void
    {
        $this->tag = $tag;
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
