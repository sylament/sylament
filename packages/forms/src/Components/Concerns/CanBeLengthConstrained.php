<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;

trait CanBeLengthConstrained
{
    protected int|Closure|null $maxLength = null;

    protected int|Closure|null $minLength = null;

    public function maxLength(int|Closure|null $length): static
    {
        $this->maxLength = $length;

        return $this;
    }

    public function minLength(int|Closure|null $length): static
    {
        $this->minLength = $length;

        return $this;
    }

    public function getMaxLength(): ?int
    {
        return $this->evaluate($this->maxLength);
    }

    public function getMinLength(): ?int
    {
        return $this->evaluate($this->minLength);
    }
}
