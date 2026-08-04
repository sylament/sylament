<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
use Twig\Markup;

trait HasChildComponents
{
    /**
     * @var array<string, Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null>
     */
    protected array $childComponents = [];

    /**
     * @var array<array-key, Schema>|null
     */
    protected ?array $cachedDefaultChildSchemas = null;

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function components(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function childComponents(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components, string $key = 'default'): static
    {
        $this->childComponents[$key] = $components;
        $this->cachedDefaultChildSchemas = null;

        return $this;
    }

    /**
     * The child schemas configured on this component: the whole cached default map
     * for the `default` key, then one per other set key. A component
     * with no child components returns an empty array — it never materializes the
     * implicit `default` schema (that only happens on an explicit
     * {@see getChildSchema()} call), so state walks don't spawn phantom schemas.
     *
     * @return array<Schema>
     */
    public function getChildSchemas(): array
    {
        $schemas = [];

        foreach (array_keys($this->childComponents) as $key) {
            if ($key === 'default') {
                foreach ($this->getCachedDefaultChildSchemas() as $defaultSchema) {
                    $schemas[] = $defaultSchema;
                }

                continue;
            }

            $schema = $this->getChildSchema((string) $key);

            if ($schema instanceof Schema) {
                $schemas[] = $schema;
            }
        }

        return $schemas;
    }

    /**
     * Whether any component nested under this one carries a state path — at any
     * depth, through any child-schema key. A leaf decorated with a path-less
     * child schema (`helperText()`, `belowContent()`, …) owns none, so it stays
     * a leaf.
     */
    public function hasPathBearingDescendants(): bool
    {
        foreach ($this->getChildSchemas() as $childSchema) {
            foreach ($childSchema->getFlatComponents(withActions: false) as $descendant) {
                if ($descendant->hasStatePath()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getChildSchema(?string $key = null): ?Schema
    {
        if (($key !== null) && ($key !== 'default') && !array_key_exists($key, $this->childComponents)) {
            return $this->getCachedDefaultChildSchemas()[$key] ?? null;
        }

        $key ??= 'default';

        $components = $this->evaluate($this->childComponents[$key] ?? []) ?: [];
        if ($components === []) {
            return $key === 'default'
                ? $this->configureChildSchema(
                    $this->makeChildSchema($key),
                    $key,
                )
                : null;
        }

        if ($components instanceof Schema) {
            return $this->configureChildSchema(
                $components
                    ->liveComponent($this->getLiveComponentOrNull())
                    ->parentComponent($this),
                $key,
            );
        }

        return $this->configureChildSchema(
            $this->makeChildSchema($key)
                ->components($components),
            $key,
        );
    }

    /**
     * @return array<array-key, Schema>
     */
    public function getDefaultChildSchemas(): array
    {
        $schema = $this->getChildSchema();

        return $schema instanceof Schema ? ['default' => $schema] : [];
    }

    public function clearCachedChildSchemas(): void
    {
        $this->cachedDefaultChildSchemas = null;
    }

    /**
     * @return array<array-key, Schema>
     */
    protected function getCachedDefaultChildSchemas(): array
    {
        if (($this->cachedDefaultChildSchemas !== null) && $this->areCachedDefaultChildSchemasFresh()) {
            return $this->cachedDefaultChildSchemas;
        }

        return $this->cachedDefaultChildSchemas = $this->getDefaultChildSchemas();
    }

    /**
     * Components whose child schemas derive from state — repeaters and their kin —
     * override this to compare the current state against the snapshot taken when
     * the cache was built, so the cache invalidates itself with no explicit
     * {@see clearCachedChildSchemas()} call.
     */
    protected function areCachedDefaultChildSchemasFresh(): bool
    {
        return true;
    }

    protected function cloneChildComponents(): static
    {
        $this->cachedDefaultChildSchemas = null;

        foreach ($this->childComponents as $key => $childComponents) {
            if (is_array($childComponents)) {
                $this->childComponents[$key] = array_map(
                    static fn (Component|Action|ActionGroup|string|Markup $component): Component|Action|ActionGroup|string|Markup => match (true) {
                        $component instanceof Component => $component->getClone(),
                        $component instanceof Action, $component instanceof ActionGroup => clone $component,
                        default => $component,
                    },
                    $childComponents,
                );
            } elseif ($childComponents instanceof Component) {
                $this->childComponents[$key] = $childComponents->getClone();
            } elseif (($childComponents instanceof Action) || ($childComponents instanceof ActionGroup)) {
                $this->childComponents[$key] = clone $childComponents;
            } elseif ($childComponents instanceof Schema) {
                $this->childComponents[$key] = $childComponents->getClone();
            }
        }

        return $this;
    }

    protected function makeChildSchema(string $key): Schema
    {
        return Schema::make($this->getLiveComponentOrNull())
            ->parentComponent($this);
    }

    protected function configureChildSchema(Schema $schema, string $key): Schema
    {
        return $schema;
    }
}
