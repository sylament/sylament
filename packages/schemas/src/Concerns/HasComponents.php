<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Html;
use Sylament\Schemas\Components\Text;
use Twig\Markup;

use function Sylament\Support\wrap;

trait HasComponents
{
    /**
     * @var array<Component|string|Markup>|Component|string|Markup|Closure
     */
    protected array|Component|string|Markup|Closure $components = [];

    /**
     * @var array<Component>|null
     */
    protected ?array $cachedComponents = null;

    /**
     * @param array<Component|string|Markup>|Component|string|Markup|Closure $components
     */
    public function components(array|Component|string|Markup|Closure $components): static
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
                if (is_string($component)) {
                    $component = Text::make($component);
                } elseif (!$component instanceof Component) {
                    $component = Html::make($component);
                }

                $components[$componentIndex] = $component->container($this);
            }

            return $components;
        })();
    }

    /**
     * Every component in this container flattened depth-first, descending through
     * each component's child schemas. Degrades to {@see getComponents()} for a flat
     * schema whose components have no child schemas.
     *
     * @return array<Component>
     */
    public function getFlatComponents(): array
    {
        $flatComponents = [];

        foreach ($this->getComponents() as $component) {
            $flatComponents[] = $component;

            foreach ($component->getChildSchemas() as $childSchema) {
                $flatComponents = [
                    ...$flatComponents,
                    ...$childSchema->getFlatComponents(),
                ];
            }
        }

        return $flatComponents;
    }
}
