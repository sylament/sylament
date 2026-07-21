<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;

trait HasChildComponents
{
    /**
     * @var array<string, Schema|array<Component>|Closure|null>
     */
    protected array $childComponents = [];

    /**
     * @param Schema|array<Component>|Closure|null $components
     */
    public function components(Schema|array|Closure|null $components): static
    {
        $this->childComponents($components);

        return $this;
    }

    /**
     * @param Schema|array<Component>|Closure|null $childComponents
     */
    public function childComponents(Schema|array|Closure|null $childComponents, string $key = 'default'): static
    {
        $this->childComponents[$key] = $childComponents;

        return $this;
    }

    public function getChildSchema(?string $key = null): ?Schema
    {
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
