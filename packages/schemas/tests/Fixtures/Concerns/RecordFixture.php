<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Concerns;

use DateTimeImmutable;
use stdClass;
use Sylament\Schemas\Tests\Fixtures\State\Casts\CastStatus;

/**
 * A plain record object standing in for a Doctrine entity. `$title` is
 * deliberately left uninitialized so the fill walk's uninitialized-property skip
 * can be exercised; `getAuthor()` is deliberately setter-less and object-valued
 * so the object-value skip can be too, and `$status` and `$publishedAt` are the
 * enum-typed and date-typed leaves a state cast carries through the skip.
 */
final class RecordFixture
{
    private ?CastStatus $status = null;

    private ?DateTimeImmutable $publishedAt = null;

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

    public function getStatus(): ?CastStatus
    {
        return $this->status;
    }

    public function setStatus(?CastStatus $status): void
    {
        $this->status = $status;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?DateTimeImmutable $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }
}
