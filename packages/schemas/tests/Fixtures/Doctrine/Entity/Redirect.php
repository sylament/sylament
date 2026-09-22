<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Two scalar identifier fields, both strings, so a key component can carry
 * every character the key grammar has to escape.
 */
#[ORM\Entity]
class Redirect
{
    #[ORM\Id]
    #[ORM\Column(length: 100)]
    private string $host = '';

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private string $path = '';

    #[ORM\Column]
    private string $label = '';

    /**
     * The owning side of the many-to-many a post holds the inverse of, and the
     * collection that makes a scalar pair a relationship owner.
     *
     * @var Collection<int, Post>
     */
    #[ORM\ManyToMany(targetEntity: Post::class, inversedBy: 'listedRedirects')]
    #[ORM\JoinTable(name: 'redirect_post_link')]
    #[ORM\JoinColumn(name: 'redirect_host', referencedColumnName: 'host')]
    #[ORM\JoinColumn(name: 'redirect_path', referencedColumnName: 'path')]
    #[ORM\InverseJoinColumn(name: 'post_id', referencedColumnName: 'id')]
    private Collection $posts;

    /**
     * @var Collection<int, Marker>
     */
    #[ORM\OneToMany(targetEntity: Marker::class, mappedBy: 'redirect')]
    private Collection $markers;

    /**
     * The inverse side of a many-to-many a single-identifier class owns, so a
     * scalar pair reaches one from the side that holds no join table of its own.
     *
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, mappedBy: 'redirects')]
    private Collection $labels;

    /**
     * @var Collection<int, MixedFirstMarker>
     */
    #[ORM\OneToMany(targetEntity: MixedFirstMarker::class, mappedBy: 'redirect')]
    private Collection $firstMixedMarkers;

    /**
     * @var Collection<int, MixedLastMarker>
     */
    #[ORM\OneToMany(targetEntity: MixedLastMarker::class, mappedBy: 'redirect')]
    private Collection $lastMixedMarkers;

    /**
     * A composite class owning a collection of its own class, so the subselect
     * names the same entity twice.
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_host', referencedColumnName: 'host')]
    #[ORM\JoinColumn(name: 'parent_path', referencedColumnName: 'path')]
    private ?Redirect $parent = null;

    /**
     * @var Collection<int, Redirect>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->markers = new ArrayCollection();
        $this->labels = new ArrayCollection();
        $this->firstMixedMarkers = new ArrayCollection();
        $this->lastMixedMarkers = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
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

    /**
     * @return Collection<int, Tag>
     */
    public function getLabels(): Collection
    {
        return $this->labels;
    }

    /**
     * @return Collection<int, MixedFirstMarker>
     */
    public function getFirstMixedMarkers(): Collection
    {
        return $this->firstMixedMarkers;
    }

    /**
     * @return Collection<int, MixedLastMarker>
     */
    public function getLastMixedMarkers(): Collection
    {
        return $this->lastMixedMarkers;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return Collection<int, Redirect>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }
}
