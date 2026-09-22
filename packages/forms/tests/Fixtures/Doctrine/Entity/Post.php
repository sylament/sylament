<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $title = '';

    #[ORM\ManyToOne]
    private ?Author $author = null;

    /**
     * @var array<array-key, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $meta = null;

    /**
     * @var Collection<int, PostSection>
     */
    #[ORM\OneToMany(targetEntity: PostSection::class, mappedBy: 'post', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid]
    private Collection $sections;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'posts')]
    private Collection $tags;

    /**
     * A collection of derived-identity children — the mapped-by field is half
     * the child's identifier, so a repeater creates and deletes these while a
     * relation-backed to-many field cannot re-point one.
     *
     * @var Collection<int, PostTranslation>
     */
    #[ORM\OneToMany(targetEntity: PostTranslation::class, mappedBy: 'post', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $translations;

    /**
     * A to-one onto a scalar pair: two join columns, one per identifier column.
     */
    #[ORM\ManyToOne(targetEntity: CompositePair::class)]
    #[ORM\JoinColumn(name: 'pair_a', referencedColumnName: 'a')]
    #[ORM\JoinColumn(name: 'pair_b', referencedColumnName: 'b')]
    private ?CompositePair $pair = null;

    /**
     * A to-one onto a derived identity, whose first identifier column is itself
     * an association's.
     */
    #[ORM\ManyToOne(targetEntity: PostTranslation::class)]
    #[ORM\JoinColumn(name: 'translation_post_id', referencedColumnName: 'post_id')]
    #[ORM\JoinColumn(name: 'translation_locale', referencedColumnName: 'locale')]
    private ?PostTranslation $translation = null;

    /**
     * A to-one onto a single derived identity — one identifier field, and it is
     * an association.
     */
    #[ORM\ManyToOne(targetEntity: PostSeal::class)]
    #[ORM\JoinColumn(name: 'seal_post_id', referencedColumnName: 'post_id')]
    private ?PostSeal $seal = null;

    /**
     * A many-to-many onto a scalar pair: one inverse join column per identifier
     * column of the target.
     *
     * @var Collection<int, CompositePair>
     */
    #[ORM\ManyToMany(targetEntity: CompositePair::class, inversedBy: 'posts')]
    #[ORM\JoinTable(name: 'post_composite_pair')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'composite_pair_a', referencedColumnName: 'a')]
    #[ORM\InverseJoinColumn(name: 'composite_pair_b', referencedColumnName: 'b')]
    private Collection $pairs;

    /**
     * A many-to-many onto a derived identity, so a to-many relation-backed
     * field reaches the flavour whose first key component is an association.
     *
     * @var Collection<int, PostTranslation>
     */
    #[ORM\ManyToMany(targetEntity: PostTranslation::class)]
    #[ORM\JoinTable(name: 'post_linked_translation')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'translation_post_id', referencedColumnName: 'post_id')]
    #[ORM\InverseJoinColumn(name: 'translation_locale', referencedColumnName: 'locale')]
    private Collection $linkedTranslations;

    public function __construct()
    {
        $this->sections = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->pairs = new ArrayCollection();
        $this->linkedTranslations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): void
    {
        $this->author = $author;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public function getMeta(): ?array
    {
        return $this->meta;
    }

    /**
     * @param array<array-key, mixed>|null $meta
     */
    public function setMeta(?array $meta): void
    {
        $this->meta = $meta;
    }

    /**
     * @return Collection<int, PostSection>
     */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function addSection(PostSection $section): void
    {
        if (! $this->sections->contains($section)) {
            $this->sections->add($section);
            $section->setPost($this);
        }
    }

    public function removeSection(PostSection $section): void
    {
        $this->sections->removeElement($section);
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): void
    {
        if (! $this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags->removeElement($tag);
    }

    /**
     * @return Collection<int, PostTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(PostTranslation $translation): void
    {
        if (! $this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setPost($this);
        }
    }

    public function removeTranslation(PostTranslation $translation): void
    {
        $this->translations->removeElement($translation);
    }

    public function getPair(): ?CompositePair
    {
        return $this->pair;
    }

    public function setPair(?CompositePair $pair): void
    {
        $this->pair = $pair;
    }

    public function getTranslation(): ?PostTranslation
    {
        return $this->translation;
    }

    public function setTranslation(?PostTranslation $translation): void
    {
        $this->translation = $translation;
    }

    public function getSeal(): ?PostSeal
    {
        return $this->seal;
    }

    public function setSeal(?PostSeal $seal): void
    {
        $this->seal = $seal;
    }

    /**
     * @return Collection<int, CompositePair>
     */
    public function getPairs(): Collection
    {
        return $this->pairs;
    }

    public function addPair(CompositePair $pair): void
    {
        if (! $this->pairs->contains($pair)) {
            $this->pairs->add($pair);
        }
    }

    public function removePair(CompositePair $pair): void
    {
        $this->pairs->removeElement($pair);
    }

    /**
     * @return Collection<int, PostTranslation>
     */
    public function getLinkedTranslations(): Collection
    {
        return $this->linkedTranslations;
    }

    public function addLinkedTranslation(PostTranslation $linkedTranslation): void
    {
        if (! $this->linkedTranslations->contains($linkedTranslation)) {
            $this->linkedTranslations->add($linkedTranslation);
        }
    }

    public function removeLinkedTranslation(PostTranslation $linkedTranslation): void
    {
        $this->linkedTranslations->removeElement($linkedTranslation);
    }
}
