<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State;

/**
 * The already-populated counterpart of {@see Post}: the nested object is
 * constructed up front, so a write mutates it in place rather than taking
 * `setOnRecord()`'s construction branch.
 */
final class InitializedPost
{
    private ?string $title = null;

    private Author $author;

    public function __construct()
    {
        $this->author = new Author();
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getAuthor(): Author
    {
        return $this->author;
    }

    public function setAuthor(Author $author): void
    {
        $this->author = $author;
    }
}
