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

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->markers = new ArrayCollection();
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
}
