<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use RuntimeException;
use Sylament\Schemas\Schema;

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

        $this->cachedSchemas[$name] = $schema;

        return $schema;
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
