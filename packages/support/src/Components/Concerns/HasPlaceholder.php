<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;

trait HasPlaceholder
{
    protected string|Markup|TranslatableInterface|Closure|null $placeholder = null;

    public function placeholder(string|Markup|TranslatableInterface|Closure|null $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getPlaceholder(): string|Markup|null
    {
        return $this->translateText($this->evaluate($this->placeholder));
    }
}
