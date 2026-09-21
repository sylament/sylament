<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A derived identity: one identifier field is a to-one association, the other a
 * scalar. It constructs with no arguments and its mapped-by property accepts
 * null, so a repeater can build one and the diffing remover can unlink it.
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

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

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

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }
}
