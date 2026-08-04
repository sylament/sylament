<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Records;

/**
 * The item entity behind {@see TaggedPost}'s collection — an object, which is
 * what makes the collection an object collection rather than a value list.
 */
final class PostTag
{
    private string $name = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
