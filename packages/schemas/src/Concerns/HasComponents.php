<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;
use Sylament\Schemas\Components\Component;

use function Sylament\Support\wrap;

trait HasComponents
{
    /**
     * @var array<Component>|Closure
     */
    protected array|Closure $components = [];

    /**
     * @var array<Component>|null
     */
    protected ?array $cachedComponents = null;

    /**
     * @param array<Component>|Closure $components
     */
    public function components(array|Closure $components): static
    {
        $this->components = $components;
        $this->cachedComponents = null;

        return $this;
    }

    /**
     * @return array<Component>
     */
    public function getComponents(): array
    {
        return $this->cachedComponents ??= (function (): array {
            $components = [];

            foreach (wrap($this->evaluate($this->components)) as $componentIndex => $component) {
                $component = $component->container($this);

                $components[$componentIndex] = $component;
            }

            return $components;
        })();
    }
}
