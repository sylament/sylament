<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use Sylament\Support\Enums\Size;

trait HasSize
{
    protected Size|string|Closure|null $size = null;

    protected Size|string|Closure|null $defaultSize = null;

    public function size(Size|string|Closure|null $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function defaultSize(Size|string|Closure|null $size): static
    {
        $this->defaultSize = $size;

        return $this;
    }

    public function getSize(): Size|string|null
    {
        return $this->evaluate($this->size) ?? $this->getDefaultSize();
    }

    public function getDefaultSize(): Size|string|null
    {
        return $this->evaluate($this->defaultSize);
    }

    public function hasSize(): bool
    {
        return $this->size !== null || $this->defaultSize !== null;
    }
}
