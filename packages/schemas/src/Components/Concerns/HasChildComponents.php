<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
use Twig\Markup;

trait HasChildComponents
{
    /**
     * @var array<string, Schema|array<Component|string|Markup>|Component|string|Markup|Closure|null>
     */
    protected array $childComponents = [];

    /**
     * @param Schema|array<Component|string|Markup>|Component|string|Markup|Closure|null $components
     */
    public function components(Schema|array|Component|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components);

        return $this;
    }

    /**
     * @param Schema|array<Component|string|Markup>|Component|string|Markup|Closure|null $components
     */
    public function childComponents(Schema|array|Component|string|Markup|Closure|null $components, string $key = 'default'): static
    {
        $this->childComponents[$key] = $components;

        return $this;
    }

    /**
     * The child schemas configured on this component, one per set key. A component
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
            $schema = $this->getChildSchema((string) $key);

            if ($schema instanceof Schema) {
                $schemas[] = $schema;
            }
        }

        return $schemas;
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
