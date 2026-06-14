<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;

trait HasChildComponents
{
    /**
     * @var array<string, Schema|array<Component>|Closure():(array<Component>|Schema)>
     */
    protected array $childComponents = [];

    /**
     * @var array<Schema>|null
     */
    protected ?array $cachedDefaultChildSchemas = null;

    /**
     * @param Schema|array<Component>|callable():(array<Component>|Schema) $components
     */
    public function components(Schema|array|callable $components): static
    {
        $this->childComponents($components);

        return $this;
    }

    /**
     * @param Schema|array<Component>|callable():(array<Component>|Schema) $childComponents
     */
    public function childComponents(Schema|array|callable $childComponents, string $key = 'default'): static
    {
        $this->childComponents[$key] = $this->evaluatable($childComponents);

        return $this;
    }

    public function getChildSchema(?string $key = null): ?Schema
    {
        if ($key !== null && array_key_exists($key, $this->cachedDefaultChildSchemas ?? [])) {
            return $this->cachedDefaultChildSchemas[$key];
        }

        $key ??= 'default';

        $components = $this->evaluate($this->childComponents[$key] ?? []) ?: [];
        if ($components === []) {
            return null;
        }

        if ($components instanceof Schema) {
            return $this->configureChildSchema(
                $components
                    ->liveComponent($this->getLiveComponent())
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

    protected function makeChildSchema(string $key): Schema
    {
        return Schema::make($this->getLiveComponent())
            ->parentComponent($this);
    }

    protected function configureChildSchema(Schema $schema, string $key): Schema
    {
        return $schema;
    }
}
