<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A derived identity: one identifier field is a to-one association, the other a
 * scalar.
 */
#[ORM\Entity]
class PostTranslation
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'translations')]
    private ?Post $post = null;

    #[ORM\Id]
    #[ORM\Column(length: 35)]
    private string $locale = '';

    #[ORM\Column]
    private string $title = '';

    /**
     * The owning side of the many-to-many a post holds the inverse of, and the
     * collection that makes a derived identity a relationship owner.
     *
     * @var Collection<int, Post>
     */
    #[ORM\ManyToMany(targetEntity: Post::class, inversedBy: 'taggedTranslations')]
    #[ORM\JoinTable(name: 'translation_post_link')]
    #[ORM\JoinColumn(name: 'translation_post_id', referencedColumnName: 'post_id')]
    #[ORM\JoinColumn(name: 'translation_locale', referencedColumnName: 'locale')]
    #[ORM\InverseJoinColumn(name: 'post_id', referencedColumnName: 'id')]
    private Collection $posts;

    /**
     * @var Collection<int, Marker>
     */
    #[ORM\OneToMany(targetEntity: Marker::class, mappedBy: 'translation')]
    private Collection $markers;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
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

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): void
    {
        if (! $this->posts->contains($post)) {
            $this->posts->add($post);
        }
    }

    public function removePost(Post $post): void
    {
        $this->posts->removeElement($post);
    }

    /**
     * @return Collection<int, Marker>
     */
    public function getMarkers(): Collection
    {
        return $this->markers;
    }
}
