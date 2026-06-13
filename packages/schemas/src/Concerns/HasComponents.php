<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;
use Sylament\Schemas\Components\Component;

trait HasComponents
{
    /**
     * @var array<Component>|Closure():array<Component>
     */
    protected array|Closure $components = [];

    /**
     * @param array<Component>|callable():array<Component> $components
     */
    public function components(array|callable $components): static
    {
        $this->components = $this->evaluatable($components);

        return $this;
    }

    /**
     * @return array<Component>
     */
    public function getComponents(): array
    {
        return $this->evaluate($this->components);
    }
}
