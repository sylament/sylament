<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Records;

/**
 * The record whose declared write-side types decide requirement: `title` is
 * non-nullable, so a blank submission cannot be written; `subtitle` accepts
 * null; `tags` is a non-nullable plain array, blank when empty and therefore
 * not a required field; `featured` is the canonical Doctrine boolean column,
 * non-nullable with a false default, which a checkbox or toggle must not turn
 * into "must be ticked"; and `author` is an optional nested object whose own
 * properties are non-nullable — the shape a blank nullable branch exists for.
 */
final class Article
{
    private string $title = '';

    private ?string $subtitle = null;

    private bool $featured = false;

    /**
     * @var array<string>
     */
    private array $tags = [];

    private ?Author $author = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): void
    {
        $this->featured = $featured;
    }

    /**
     * @return array<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param array<string> $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): void
    {
        $this->author = $author;
    }
}
