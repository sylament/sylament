<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;

trait HasDescription
{
    protected string|Markup|TranslatableInterface|Closure|null $description = null;

    public function description(string|Markup|TranslatableInterface|Closure|null $description = null): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string|Markup|null
    {
        return $this->translateText($this->evaluate($this->description));
    }
}
