<?php

declare(strict_types=1);

namespace Sylament\Panels\Panel\Concerns;

trait HasRoutes
{
    protected string $path = '';

    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
