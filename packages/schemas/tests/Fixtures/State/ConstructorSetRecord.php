<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State;

use DateTimeImmutable;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Category;
use Symfony\Component\Uid\Uuid;

/**
 * A record whose constructor sets properties no setter can change, in each
 * shape the mapper's leaf rule reads — public and readonly, private behind a
 * getter, private with no getter at all — over the values it compares: scalars,
 * a bool, a date, a Uid, a value object and a record association. `$title` and
 * `$category` are the writable controls, and `$title` counts its setter's calls.
 */
final class ConstructorSetRecord
{
    private ?string $title = null;

    private int $titleWrites = 0;

    private ?Category $category = null;

    public function __construct(
        public readonly string $host,
        private readonly string $path,
        private readonly string $secret,
        public readonly ?int $rank = null,
        public readonly ?bool $active = null,
        public readonly ?DateTimeImmutable $publishedAt = null,
        public readonly ?Uuid $token = null,
        public readonly ?Money $price = null,
        public readonly ?Category $section = null,
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function matchesSecret(string $candidate): bool
    {
        return $this->secret === $candidate;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
        $this->titleWrites++;
    }

    public function getTitleWrites(): int
    {
        return $this->titleWrites;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): void
    {
        $this->category = $category;
    }
}
