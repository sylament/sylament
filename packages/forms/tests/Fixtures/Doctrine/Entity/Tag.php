<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $name = '';

    /**
     * The self-referential association `relationship(ignoreRecord: true)` needs:
     * the option query's root and the bound record are the same entity, so the
     * record must be able to exclude itself from its own parent options.
     */
    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?self $parent = null;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\ManyToMany(targetEntity: Post::class, mappedBy: 'tags')]
    private Collection $posts;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
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
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    /**
     * Deliberately does NOT call back to {@see Post::addTag()}: the inverse side
     * heals nothing, so an inverse-side save that wrote only this collection
     * would be lost at flush. A maker-style adder that syncs the owning side
     * would write the join row itself and hide exactly that.
     */
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
}
