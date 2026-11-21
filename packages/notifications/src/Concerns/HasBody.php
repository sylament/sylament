<?php

declare(strict_types=1);

namespace Sylament\Notifications\Concerns;

use Closure;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;

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
        $body = $this->translateText($this->evaluate($this->body));

        return $body instanceof Markup ? (string) $body : $body;
    }
}
