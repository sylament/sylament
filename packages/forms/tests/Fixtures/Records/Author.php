<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Records;

/**
 * The optional sub-object of {@see Article}: `name` and `handle` are
 * non-nullable, so every leaf beneath the branch derives required — which is
 * what makes the blank-branch suppression observable. `verified` is the boolean
 * leaf a checkbox binds to, resting at false.
 */
final class Author
{
    private string $name = '';

    private string $handle = '';

    private bool $verified = false;

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

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): void
    {
        $this->verified = $verified;
    }
}
