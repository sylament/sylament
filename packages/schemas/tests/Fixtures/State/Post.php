<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State;

/**
 * A record whose nested object is nullable and defaults to null, so the
 * missing-intermediate arms can be exercised — a lenient read past it, and a
 * write that constructs it from its declared type; `$meta` is the array-backed
 * branch (the JSON-column shape).
 */
final class Post
{
    private ?string $title = null;

    private ?Author $author = null;

    /** @var array<string, mixed>|null */
    private ?array $meta = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
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

    /** @return array<string, mixed>|null */
    public function getMeta(): ?array
    {
        return $this->meta;
    }

    /** @param array<string, mixed>|null $meta */
    public function setMeta(?array $meta): void
    {
        $this->meta = $meta;
    }
}
