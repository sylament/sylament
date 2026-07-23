<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Html;
use Sylament\Schemas\Components\Text;
use Twig\Markup;

use function Sylament\Support\blank;
use function Sylament\Support\filled;
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
    public function getComponents(bool $withHidden = true): array
    {
        $components = $this->cachedComponents ??= (function (): array {
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

        if ($withHidden) {
            return $components;
        }

        return array_filter(
            $components,
            static fn (Component $component): bool => $component->isVisible(),
        );
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

    /**
     * Resolve the component owning an absolute state path, descending into child
     * schemas. Never descends into the children of a component in
     * `$skipComponentsChildContainersWhileSearching` (identity), so a component's
     * own `$get`/`$set` cannot recurse into itself. Callers pass absolute paths.
     *
     * @param array<Component> $skipComponentsChildContainersWhileSearching
     */
    public function getComponentByStatePath(string $statePath, array $skipComponentsChildContainersWhileSearching = []): ?Component
    {
        foreach ($this->getComponents() as $component) {
            $componentStatePath = $component->getStatePath();

            if (filled($componentStatePath) && $componentStatePath === $statePath) {
                return $component;
            }

            if (in_array($component, $skipComponentsChildContainersWhileSearching, strict: true)) {
                continue;
            }

            if (blank($componentStatePath) || str_starts_with($statePath, "{$componentStatePath}.")) {
                foreach ($component->getChildSchemas() as $childSchema) {
                    $found = $childSchema->getComponentByStatePath($statePath, $skipComponentsChildContainersWhileSearching);

                    if ($found !== null) {
                        return $found;
                    }
                }
            }
        }

        return null;
    }
}
