<?php

declare(strict_types=1);

namespace Sylament\Notifications\Concerns;

use Closure;
use Symfony\Contracts\Translation\TranslatableInterface;

trait HasBody
{
    protected string|TranslatableInterface|Closure|null $body = null;

    public function body(string|TranslatableInterface|Closure|null $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->translateText($this->evaluate($this->body));
    }
}
