<?php

declare(strict_types=1);

namespace Sylament\Panels\Panel\Concerns;

use Closure;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;

trait HasBrandName
{
    protected string|Markup|TranslatableInterface|Closure|null $brandName = null;

    public function brandName(string|Markup|TranslatableInterface|Closure|null $name): static
    {
        $this->brandName = $name;

        return $this;
    }

    public function getBrandName(): string|Markup|null
    {
        return $this->translateText($this->evaluate($this->brandName));
    }
}
