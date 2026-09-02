<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;
use Twig\Markup;

trait HasPlaceholder
{
    protected string|Markup|Closure|null $placeholder = null;

    public function placeholder(string|Markup|Closure|null $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getPlaceholder(): string|Markup|null
    {
        return $this->evaluate($this->placeholder);
    }
}
