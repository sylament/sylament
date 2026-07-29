<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use RuntimeException;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\Attribute\PreDehydrate;

trait HasSchemas
{
    /**
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

    public function cacheSchema(string $name, ?Schema $schema): ?Schema
    {
        if ($schema instanceof Schema) {
            return $this->cachedSchemas[$name] = $schema->key($name);
        }

        unset($this->cachedSchemas[$name]);

        return null;
    }

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
