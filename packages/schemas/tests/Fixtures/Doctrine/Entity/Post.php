<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $title = '';

    #[ORM\ManyToOne(inversedBy: 'posts')]
    private ?Author $author = null;

    #[ORM\OneToOne(mappedBy: 'post', cascade: ['persist'], orphanRemoval: true)]
    private ?Profile $profile = null;

    #[ORM\OneToOne(cascade: ['persist'])]
    private ?Byline $byline = null;

    /**
     * @var Collection<int, PostSection>
     */
    #[ORM\OneToMany(targetEntity: PostSection::class, mappedBy: 'post', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $sections;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    private Collection $tags;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'posts')]
    private Collection $categories;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post')]
    private Collection $comments;

    /**
     * Ordered **descending**, the one mapping order here that is, so a reader
     * that upper-cased or hardcoded the direction could not pass unnoticed.
     *
     * @var Collection<int, Reply>
     */
    #[ORM\OneToMany(targetEntity: Reply::class, mappedBy: 'post')]
    #[ORM\OrderBy(['body' => 'DESC'])]
    private Collection $replies;

    /**
     * The one-to-many whose mapped-by field is half the child's identifier, so
     * pointing an existing child here would move its primary key rather than
     * the child. {@see $comments} is the ordinary contrast.
     *
     * @var Collection<int, PostTranslation>
     */
    #[ORM\OneToMany(targetEntity: PostTranslation::class, mappedBy: 'post')]
    private Collection $translations;

    /**
     * @var Collection<int, Redirect>
     */
    #[ORM\ManyToMany(targetEntity: Redirect::class)]
    #[ORM\JoinTable(name: 'post_redirect_link')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'redirect_host', referencedColumnName: 'host')]
    #[ORM\InverseJoinColumn(name: 'redirect_path', referencedColumnName: 'path')]
    private Collection $redirects;

    /**
     * @var Collection<int, Redirect>
     */
    #[ORM\ManyToMany(targetEntity: Redirect::class, mappedBy: 'posts')]
    private Collection $listedRedirects;

    /**
     * @var Collection<int, PostTranslation>
     */
    #[ORM\ManyToMany(targetEntity: PostTranslation::class)]
    #[ORM\JoinTable(name: 'post_translation_link')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'translation_post_id', referencedColumnName: 'post_id')]
    #[ORM\InverseJoinColumn(name: 'translation_locale', referencedColumnName: 'locale')]
    private Collection $linkedTranslations;

    /**
     * @var Collection<int, PostTranslation>
     */
    #[ORM\ManyToMany(targetEntity: PostTranslation::class, mappedBy: 'posts')]
    private Collection $taggedTranslations;

    /**
     * @var Collection<int, PostSeal>
     */
    #[ORM\ManyToMany(targetEntity: PostSeal::class)]
    #[ORM\JoinTable(name: 'post_seal_link')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'seal_post_id', referencedColumnName: 'post_id')]
    private Collection $seals;

    /**
     * @var Collection<int, Enrolment>
     */
    #[ORM\ManyToMany(targetEntity: Enrolment::class)]
    #[ORM\JoinTable(name: 'post_enrolment_link')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'enrolment_post_id', referencedColumnName: 'post_id')]
    #[ORM\InverseJoinColumn(name: 'enrolment_tag_id', referencedColumnName: 'tag_id')]
    private Collection $enrolments;

    public function __construct()
    {
        $this->sections = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->replies = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->redirects = new ArrayCollection();
        $this->listedRedirects = new ArrayCollection();
        $this->linkedTranslations = new ArrayCollection();
        $this->taggedTranslations = new ArrayCollection();
        $this->seals = new ArrayCollection();
        $this->enrolments = new ArrayCollection();
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

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): void
    {
        if ($profile instanceof Profile && $profile->getPost() !== $this) {
            $profile->setPost($this);
        }

        $this->profile = $profile;
    }

    public function getByline(): ?Byline
    {
        return $this->byline;
    }

    public function setByline(?Byline $byline): void
    {
        $this->byline = $byline;
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
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): void
    {
        if (! $this->categories->contains($category)) {
            $this->categories->add($category);
        }
    }

    public function removeCategory(Category $category): void
    {
        $this->categories->removeElement($category);
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * @return Collection<int, Reply>
     */
    public function getReplies(): Collection
    {
        return $this->replies;
    }

    /**
     * @return Collection<int, PostTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    /**
     * @return Collection<int, Redirect>
     */
    public function getRedirects(): Collection
    {
        return $this->redirects;
    }

    public function addRedirect(Redirect $redirect): void
    {
        if (! $this->redirects->contains($redirect)) {
            $this->redirects->add($redirect);
        }
    }

    public function removeRedirect(Redirect $redirect): void
    {
        $this->redirects->removeElement($redirect);
    }

    /**
     * @return Collection<int, Redirect>
     */
    public function getListedRedirects(): Collection
    {
        return $this->listedRedirects;
    }

    /**
     * Deliberately does NOT call back to {@see Redirect::addPost()}: the
     * inverse side heals nothing, so a write mis-routed here is lost at flush
     * exactly as Doctrine's mapping says it is.
     */
    public function addListedRedirect(Redirect $listedRedirect): void
    {
        if (! $this->listedRedirects->contains($listedRedirect)) {
            $this->listedRedirects->add($listedRedirect);
        }
    }

    public function removeListedRedirect(Redirect $listedRedirect): void
    {
        $this->listedRedirects->removeElement($listedRedirect);
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

    /**
     * @return Collection<int, PostTranslation>
     */
    public function getTaggedTranslations(): Collection
    {
        return $this->taggedTranslations;
    }

    public function addTaggedTranslation(PostTranslation $taggedTranslation): void
    {
        if (! $this->taggedTranslations->contains($taggedTranslation)) {
            $this->taggedTranslations->add($taggedTranslation);
        }
    }

    public function removeTaggedTranslation(PostTranslation $taggedTranslation): void
    {
        $this->taggedTranslations->removeElement($taggedTranslation);
    }

    /**
     * @return Collection<int, PostSeal>
     */
    public function getSeals(): Collection
    {
        return $this->seals;
    }

    public function addSeal(PostSeal $seal): void
    {
        if (! $this->seals->contains($seal)) {
            $this->seals->add($seal);
        }
    }

    public function removeSeal(PostSeal $seal): void
    {
        $this->seals->removeElement($seal);
    }

    /**
     * @return Collection<int, Enrolment>
     */
    public function getEnrolments(): Collection
    {
        return $this->enrolments;
    }

    public function addEnrolment(Enrolment $enrolment): void
    {
        if (! $this->enrolments->contains($enrolment)) {
            $this->enrolments->add($enrolment);
        }
    }

    public function removeEnrolment(Enrolment $enrolment): void
    {
        $this->enrolments->removeElement($enrolment);
    }
}
