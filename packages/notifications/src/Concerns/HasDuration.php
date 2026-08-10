<?php

declare(strict_types=1);

namespace Sylament\Notifications\Concerns;

use Closure;

trait HasDuration
{
    protected int|string|Closure $duration = 6000;

    public function duration(int|string|Closure|null $duration): static
    {
        $this->duration = $duration ?? 'persistent';

        return $this;
    }

    public function getDuration(): int|string
    {
        return $this->evaluate($this->duration);
    }

    public function seconds(float $seconds): static
    {
        return $this->duration((int) ($seconds * 1000));
    }

    public function persistent(): static
    {
        return $this->duration('persistent');
    }
}
