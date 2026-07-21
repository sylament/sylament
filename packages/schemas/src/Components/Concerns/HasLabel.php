<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Twig\Markup;

trait HasLabel
{
    /**
     * @var bool|Closure():bool
     */
    protected bool|Closure $isLabelHidden = false;

    /**
     * @var string|Markup|Closure():(string|Markup)|null
     */
    protected string|Markup|Closure|null $label = null;

    /**
     * @param bool|callable():bool $condition
     */
    public function hiddenLabel(bool|callable $condition = true): static
    {
        $this->isLabelHidden = $this->evaluatable($condition);

        return $this;
    }

    public function isLabelHidden(): bool
    {
        return $this->evaluate($this->isLabelHidden);
    }

    /**
     * @param string|Markup|callable():(string|Markup)|null $label
     */
    public function label(string|Markup|callable|null $label): static
    {
        $this->label = $this->evaluatable($label);

        return $this;
    }

    public function getLabel(): string|Markup|null
    {
        return $this->evaluate($this->label);
    }

    public function hasCustomLabel(): bool
    {
        return $this->label !== null;
    }
}
