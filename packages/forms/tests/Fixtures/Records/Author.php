<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Records;

/**
 * The optional sub-object of {@see Article}: both properties are non-nullable,
 * so every leaf beneath the branch derives required — which is what makes the
 * blank-branch suppression observable.
 */
final class Author
{
    private string $name = '';

    private string $handle = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function setHandle(string $handle): void
    {
        $this->handle = $handle;
    }
}
