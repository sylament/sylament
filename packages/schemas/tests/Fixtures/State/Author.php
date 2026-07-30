<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State;

final class Author
{
    public function __construct(
        private ?string $name = null,
        private ?string $handle = null,
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getHandle(): ?string
    {
        return $this->handle;
    }

    public function setHandle(?string $handle): void
    {
        $this->handle = $handle;
    }
}
