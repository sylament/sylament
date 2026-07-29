<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Concerns;

use stdClass;

/**
 * A plain record object standing in for a Doctrine entity. `$title` is
 * deliberately left uninitialized so the fill walk's uninitialized-property skip
 * can be exercised; `getAuthor()` is deliberately setter-less and object-valued
 * so the object-value skip can be too.
 */
final class RecordFixture
{
    private string $title;

    private ?string $perex = null;

    /** @var list<string> */
    private array $tags = [];

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getPerex(): ?string
    {
        return $this->perex;
    }

    public function setPerex(?string $perex): void
    {
        $this->perex = $perex;
    }

    /** @return list<string> */
    public function getTags(): array
    {
        return $this->tags;
    }

    /** @param list<string> $tags */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function getAuthor(): stdClass
    {
        return new stdClass();
    }
}
