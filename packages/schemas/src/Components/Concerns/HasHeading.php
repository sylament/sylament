<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Twig\Markup;

trait HasHeading
{
    /**
     * @var string|Markup|Closure():(string|Markup)|null
     */
    protected string|Markup|Closure|null $heading = null;

    /**
     * @param string|Markup|callable():(string|Markup)|null $heading
     */
    public function heading(string|Markup|callable|null $heading): static
    {
        $this->heading = $this->evaluatable($heading);

        return $this;
    }

    public function getHeading(): string|Markup|null
    {
        return $this->evaluate($this->heading);
    }
}
