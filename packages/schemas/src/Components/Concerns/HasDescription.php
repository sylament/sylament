<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Twig\Markup;

trait HasDescription
{
    protected string|Markup|Closure|null $description = null;

    public function description(string|Markup|Closure|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string|Markup|null
    {
        return $this->evaluate($this->description);
    }
}
