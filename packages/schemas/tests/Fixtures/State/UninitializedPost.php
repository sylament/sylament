<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State;

/**
 * The never-assigned counterpart of {@see InitializedPost}: the nested object is
 * declared non-nullable and never assigned, so reading it raises
 * `UninitializedPropertyException` rather than yielding the null {@see Post}
 * gives — the arm proving construction covers an uninitialized typed property,
 * not only an explicit null.
 */
final class UninitializedPost
{
    private Author $author;

    public function getAuthor(): Author
    {
        return $this->author;
    }

    public function setAuthor(Author $author): void
    {
        $this->author = $author;
    }
}
