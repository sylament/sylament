<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;

trait HasHeading
{
    protected string|Markup|TranslatableInterface|Closure|null $heading = null;

    public function heading(string|Markup|TranslatableInterface|Closure|null $heading = null): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function getHeading(): string|Markup|null
    {
        return $this->translateText($this->evaluate($this->heading));
    }
}
