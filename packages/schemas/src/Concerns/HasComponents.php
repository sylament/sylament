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
     * Deep-clone this schema's own component definitions — components through
     * {@see Component::getClone()}, actions and action groups as shallow copies.
     */
    protected function cloneComponents(): static
    {
        if (!$this->components instanceof Closure) {
            $this->components = array_map(
                static fn (Component|Action|ActionGroup|string|Markup $component): Component|Action|ActionGroup|string|Markup => match (true) {
                    $component instanceof Component => $component->getClone(),
                    $component instanceof Action, $component instanceof ActionGroup => clone $component,
                    default => $component,
                },
                wrap($this->components),
            );
        }

        $this->cachedComponents = null;

        return $this;
    }

    /**
     * Resolve an action anywhere in this container by name: a direct `Action`
     * child, an action inside a direct `ActionGroup` child, a
     * component-registered action (reached only when `$nestedContainerKey`
     * exactly matches the owning component's relative key), or recursively
     * through each component's child schemas — keyed child schemas are entered
     * only when the remaining nested key targets them, unkeyed ones pass it
     * through. Depth-first, first match wins; hidden components are not
     * searched.
     */
    public function getAction(string $actionName, ?string $nestedContainerKey = null): ?Action
    {
        foreach ($this->getComponents(withHidden: false) as $component) {
            if (blank($nestedContainerKey)) {
                if (
                    ($component instanceof Action)
                    && ($component->getName() === $actionName)
                ) {
                    return $component;
                }

                if ($component instanceof ActionGroup) {
                    $groupAction = $component->getFlatActions()[$actionName] ?? null;

                    if ($groupAction !== null) {
                        return $groupAction;
                    }
                }
            }

            if ($component instanceof Action) {
                continue;
            }

            if ($component instanceof ActionGroup) {
                continue;
            }

            $componentKey = $component->getKey(isAbsolute: false);

            if (filled($componentKey)) {
                $componentInheritanceKey = $component->getInheritanceKey(isAbsolute: false);

                if (
                    filled($nestedContainerKey)
                    && ($nestedContainerKey !== $componentKey)
                    && filled($componentInheritanceKey)
                    && !str_starts_with($nestedContainerKey, "{$componentInheritanceKey}.")
                ) {
                    continue;
                }

                if ($nestedContainerKey === $componentKey) {
                    $componentAction = $component->getAction($actionName);

                    if ($componentAction !== null) {
                        return $componentAction;
                    }

                    $componentNestedContainerKey = null;
                } elseif (filled($nestedContainerKey) && filled($componentInheritanceKey)) {
                    $componentNestedContainerKey = mb_substr($nestedContainerKey, mb_strlen("{$componentInheritanceKey}."));
                } else {
                    $componentNestedContainerKey = $nestedContainerKey;
                }
            } else {
                $componentNestedContainerKey = $nestedContainerKey;
            }

            foreach ($component->getChildSchemas() as $childSchema) {
                $childSchemaName = $childSchema->getKey(isAbsolute: false);

                if (filled($childSchemaName)) {
                    if (blank($componentNestedContainerKey)) {
                        continue;
                    }

                    if (
                        ($componentNestedContainerKey !== $childSchemaName)
                        && !str_starts_with($componentNestedContainerKey, "{$childSchemaName}.")
                    ) {
                        continue;
                    }

                    $childSchemaNestedContainerKey = ($componentNestedContainerKey === $childSchemaName)
                        ? null
                        : mb_substr($componentNestedContainerKey, mb_strlen("{$childSchemaName}."));
                } else {
                    $childSchemaNestedContainerKey = $componentNestedContainerKey;
                }

                $childSchemaAction = $childSchema->getAction($actionName, $childSchemaNestedContainerKey);

                if ($childSchemaAction !== null) {
                    return $childSchemaAction;
                }
            }
        }

        return null;
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
