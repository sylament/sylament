<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use RuntimeException;
use Sylament\Schemas\Schema;

/**
 * Host-side schema management. The host declares its own state as
 * `#[LiveProp(writable: true)]` properties — a `data` array bag, individual
 * typed props, or one bag per form — and each schema-defining method binds to
 * them via `->statePath(...)`, mirroring Filament's
 * `->statePath($this->getFormStatePath())` (which likewise defaults to no path,
 * i.e. top-level binding). The trait presumes no state shape, so a single host
 * can carry several independently-bound schemas without them colliding.
 */
trait HasSchemas
{
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

        if (!method_exists($this, $name)) {
            throw new RuntimeException("Schema {$name} does not exist on component " . $this::class);
        }

        $schema = $this->$name($this->makeSchema());
        if (!$schema instanceof Schema) {
            throw new RuntimeException('Schema must be an instance of ' . Schema::class);
        }

        return $this->cachedSchemas[$name] = $schema->key($name);
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

    public function makeSchema(): Schema
    {
        return Schema::make($this);
    }
}
