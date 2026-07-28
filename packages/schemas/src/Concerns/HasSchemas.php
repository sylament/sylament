<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use RuntimeException;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\Attribute\PreDehydrate;

/**
 * Host-side schema management. The host declares its own state as
 * `#[LiveProp(writable: true)]` properties — a `data` array bag, individual
 * typed props, or one bag per form — and each schema-defining method binds to
 * them via `->statePath(...)`, mirroring Filament's
 * `->statePath($this->getFormStatePath())` (which likewise defaults to no path,
 * i.e. top-level binding). The trait presumes no state shape, so a single host
 * can carry several independently-bound schemas without them colliding.
 *
 * Each name in `schemas()` resolves to a public method taking a `Schema` and
 * returning one, named either for the schema itself (`article()`) or with
 * Filament's suffix (`articleSchema()`); the bare name wins when both exist.
 * The schema is keyed by the declared name either way, never the method name.
 *
 * `cacheSchema()` lets a caller register a built `Schema` directly, bypassing
 * method discovery entirely — the mounted-action modal uses it to cache the
 * action's schema under a synthetic name. Because `getCachedSchemas()` returns
 * the whole `$cachedSchemas` map, a schema registered this way is picked up by
 * `persistOldState()` and `callAfterSchemaStateUpdated()` for free — that is
 * what makes reactive fields work inside a mounted-action modal.
 */
trait HasSchemas
{
    /**
     * Raw state snapshot keyed by absolute state path, captured at the end of
     * each render (`#[PreDehydrate]`) and diffed against the next request's
     * hydrated state (`#[PostHydrate]`) to fire `afterStateUpdated` — the port's
     * stand-in for Livewire's path-keyed `updated*` hook, which UX Live
     * Components lack. Roughly doubles the wire state; accepted (design §2).
     *
     * @var array<string, mixed>
     */
    #[LiveProp]
    public array $oldState = [];

    /**
     * @var array<string, Schema>
     */
    protected array $cachedSchemas = [];

    public function schemas(): array
    {
        return [
            'schema',
        ];
    }

    public function getSchema(string $name = 'schema'): Schema
    {
        $cachedSchema = $this->cachedSchemas[$name] ?? null;
        if ($cachedSchema instanceof Schema) {
            return $cachedSchema;
        }

        $method = match (true) {
            method_exists($this, $name) => $name,
            method_exists($this, "{$name}Schema") => "{$name}Schema",
            default => throw new RuntimeException("Schema {$name} does not exist on component " . $this::class . " — expected a {$name}() or {$name}Schema() method."),
        };

        $schema = $this->{$method}($this->makeSchema());
        if (!$schema instanceof Schema) {
            throw new RuntimeException('Schema must be an instance of ' . Schema::class);
        }

        return $this->cachedSchemas[$name] = $schema->key($name);
    }

    /**
     * Register a schema instance under a name, or unset the cache entry when
     * `$schema` is null. Filament's counterpart also builds the schema itself
     * when called with a single argument (its `func_num_args() === 1` arm
     * triggers method-name discovery); ours has no such arm — `getSchema()`
     * already owns discovery — so `$schema` is required, and passing null is
     * always an explicit unset, never a build trigger.
     */
    public function cacheSchema(string $name, ?Schema $schema): ?Schema
    {
        if ($schema instanceof Schema) {
            return $this->cachedSchemas[$name] = $schema->key($name);
        }

        unset($this->cachedSchemas[$name]);

        return null;
    }

    /**
     * A pure membership test against the raw `$cachedSchemas` property, unlike
     * Filament's `hasCachedSchema()`, which checks `getCachedSchemas()` and so
     * builds every method-discovered schema just to answer the question. Both
     * Sylament callers — the mounted-action schema lookup and the
     * schema-component action resolver — want membership without the build.
     */
    public function hasCachedSchema(string $name): bool
    {
        return array_key_exists($name, $this->cachedSchemas);
    }

    /**
     * Fill the default schema. Multi-schema hosts fill each explicitly via
     * `getSchema($name)->fill(...)`.
     *
     * @param array<string, mixed>|null $state
     */
    public function fill(?array $state = null): static
    {
        $this->getSchema()->fill($state);

        return $this;
    }

    /**
     * @return array<string, Schema>
     */
    public function getCachedSchemas(): array
    {
        foreach ($this->schemas() as $name) {
            if (!array_key_exists((string) $name, $this->cachedSchemas)) {
                $this->getSchema($name);
            }
        }

        return $this->cachedSchemas;
    }

    public function getOldSchemaState(?string $path): mixed
    {
        if ($path === null) {
            return null;
        }

        return $this->oldState[$path] ?? null;
    }

    /**
     * Snapshot every bound component's raw state at the end of the render, keyed
     * by absolute state path, so the next round-trip can diff against it.
     */
    #[PreDehydrate]
    public function persistOldState(): void
    {
        $oldState = [];

        foreach ($this->getCachedSchemas() as $schema) {
            foreach ($schema->getFlatComponents(withActions: false) as $component) {
                if (!$component->hasStatePath()) {
                    continue;
                }

                $oldState[(string) $component->getStatePath()] = $component->getRawState();
            }
        }

        $this->oldState = $oldState;
    }

    /**
     * After the round-trip re-hydrates state, fire the `afterStateUpdated` hooks
     * of every component whose raw state changed since the snapshot. Named
     * distinctly from the component-level `afterStateUpdated()` setter (the
     * prototype collided both).
     *
     * The explicit low `#[PostHydrate]` priority is load-bearing: schemas that no
     * `schemas()` name can rebuild — a mounted action's, registered by the stack
     * resolution that `Sylament\Actions\Concerns\HasActions`'s own
     * `#[PostHydrate(priority: 10)]` hook runs — must already be in
     * `$cachedSchemas` when this walks them, or fields inside an open modal never
     * fire. Higher priority runs earlier (`PostHydrate::$priority`).
     */
    #[PostHydrate(priority: -10)]
    public function callAfterSchemaStateUpdated(): void
    {
        foreach ($this->getCachedSchemas() as $schema) {
            foreach ($schema->getFlatComponents(withActions: false) as $component) {
                if (!$component->hasStatePath()) {
                    continue;
                }

                if ($this->getOldSchemaState($component->getStatePath()) === $component->getRawState()) {
                    continue;
                }

                $component->callAfterStateUpdatedHooks();
            }
        }
    }

    public function makeSchema(): Schema
    {
        return Schema::make($this);
    }
}
