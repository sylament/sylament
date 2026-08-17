<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;

trait CanAllowHtml
{
    // Security: an option label renders raw once this is enabled, so nothing
    // escapes it. Only allow HTML for content you control — never for raw user
    // input without sanitization first.

    protected bool|Closure $isHtmlAllowed = false;

    public function allowHtml(bool|Closure $condition = true): static
    {
        $this->isHtmlAllowed = $condition;

        return $this;
    }

    public function isHtmlAllowed(): bool
    {
        return (bool) $this->evaluate($this->isHtmlAllowed);
    }
}
