<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State;

/**
 * The happy-path counterpart of {@see Post}: the nested object is constructed
 * up front, the pattern `setOnRecord()`'s missing-intermediate LogicException
 * tells users to adopt.
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
