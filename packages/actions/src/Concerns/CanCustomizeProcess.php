<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

trait CanCustomizeProcess
{
    protected ?Closure $using = null;

    public function using(?Closure $using): static
    {
        $this->using = $using;

        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function process(?Closure $default, array $parameters = []): mixed
    {
        return $this->evaluate($this->using ?? $default, $parameters);
    }
}
