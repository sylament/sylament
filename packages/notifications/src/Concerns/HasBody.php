<?php

declare(strict_types=1);

namespace Sylament\Notifications\Concerns;

use Closure;

trait HasBody
{
    protected string|Closure|null $body = null;

    public function body(string|Closure|null $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->evaluate($this->body);
    }
}
