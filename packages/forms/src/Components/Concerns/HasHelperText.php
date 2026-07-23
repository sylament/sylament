<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Twig\Markup;

trait HasHelperText
{
    protected string|Markup|Closure|null $helperText = null;

    public function helperText(string|Markup|Closure|null $text): static
    {
        $this->helperText = $text;

        return $this;
    }

    public function getHelperText(): string|Markup|null
    {
        return $this->evaluate($this->helperText);
    }
}
