<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Records;

final class Article
{
    private string $title = '';

    private ?Profile $author = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getAuthor(): ?Profile
    {
        return $this->author;
    }

    public function setAuthor(?Profile $author): void
    {
        $this->author = $author;
    }
}
