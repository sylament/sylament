<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
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
     * @var array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure
     */
    protected array|Component|Action|ActionGroup|string|Markup|Closure $components = [];

    /**
     * @var array<Component|Action|ActionGroup>|null
     */
    protected ?array $cachedComponents = null;

    /**
     * @param array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure $components
     */
    public function components(array|Component|Action|ActionGroup|string|Markup|Closure $components): static
    {
        $this->components = $components;
        $this->cachedComponents = null;

        return $this;
    }

    /**
     * @return ($withActions is true ? array<Component|Action|ActionGroup> : array<Component>)
     */
    public function getComponents(bool $withActions = true, bool $withHidden = true): array
    {
        $components = $this->cachedComponents ??= (function (): array {
            $components = [];

            foreach (wrap($this->evaluate($this->components)) as $componentIndex => $component) {
                if ($component instanceof Action) {
                    $this->modifyAction($component);
                }

                if ($component instanceof ActionGroup) {
                    $this->modifyActionGroup($component);
                }

                if (($component instanceof Action) || ($component instanceof ActionGroup)) {
                    $components[$componentIndex] = $component->schemaContainer($this);

                    continue;
                }

                if (is_string($component)) {
                    $component = Text::make($component);
                } elseif (!$component instanceof Component) {
                    $component = Html::make($component);
                }

                $components[$componentIndex] = $component->container($this);
            }

            return $components;
        })();

        if ($withActions && $withHidden) {
            return $components;
        }

        return array_filter(
            $components,
            static function (Component|Action|ActionGroup $component) use ($withActions, $withHidden): bool {
                if (!$withActions && (($component instanceof Action) || ($component instanceof ActionGroup))) {
                    return false;
                }

                return $withHidden || $component->isVisible();
            },
        );
    }

    /**
     * Every component in this container flattened depth-first, descending through
     * each component's child schemas. Actions and action groups are appended but
     * never descended into. Degrades to {@see getComponents()} for a flat schema
     * whose components have no child schemas.
     *
     * @return ($withActions is true ? array<Component|Action|ActionGroup> : array<Component>)
     */
    public function getFlatComponents(bool $withActions = true): array
    {
        $flatComponents = [];

        foreach ($this->getComponents($withActions) as $component) {
            $flatComponents[] = $component;

            if (!$component instanceof Component) {
                continue;
            }

            foreach ($component->getChildSchemas() as $childSchema) {
                $flatComponents = [
                    ...$flatComponents,
                    ...$childSchema->getFlatComponents($withActions),
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
        foreach ($this->getComponents(withActions: false) as $component) {
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
