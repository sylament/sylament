<?php

declare(strict_types=1);

namespace Sylament\Panels\Panel\Concerns;

use Closure;
use Twig\Markup;

trait HasBrandName
{
    protected string|Markup|Closure|null $brandName = null;

    public function brandName(string|Markup|Closure|null $name): static
    {
        $this->brandName = $name;

        return $this;
    }

    public function getBrandName(): string|Markup|null
    {
        return $this->evaluate($this->brandName);
    }
}
