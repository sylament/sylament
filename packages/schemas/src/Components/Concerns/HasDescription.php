<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Twig\Markup;

trait HasDescription
{
    /**
     * @var string|Markup|Closure():(string|Markup)|null
     */
    protected string|Markup|Closure|null $description = null;

    /**
     * @param string|Markup|callable():(string|Markup)|null $description
     */
    public function description(string|Markup|callable|null $description): static
    {
        $this->description = $this->evaluatable($description);

        return $this;
    }

    public function getDescription(): string|Markup|null
    {
        return $this->evaluate($this->description);
    }
}
