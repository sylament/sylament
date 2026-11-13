<?php

declare(strict_types=1);

namespace Sylament\Forms\Testing;

use Closure;
use Sylament\Actions\Testing\Testable;
use Sylament\Schemas\State\PropertyAccessor;

use function Sylament\Support\filled;

/**
 * @phpstan-require-extends Testable
 */
trait TestsForms
{
    /**
     * Sends one live request per key, in the given order, so each field's
     * `afterStateUpdated` hooks run against the value just written, as Filament's
     * `fillFormDataForTesting()` loop runs them.
     *
     * @param array<string, mixed>|Closure $state
     */
    public function fillForm(array|Closure $state = [], ?string $form = null): static
    {
        $form = $this->resolveTestedSchemaName($form);

        $this->assertSchemaExists($form);

        $schema = $this->getTestedSchema($this->getTestedSchemasHost(), $form);
        $schemaStatePath = $schema->getStatePath();
        $propertyAccessor = $schema->getPropertyAccessor();

        if ($state instanceof Closure) {
            $state = $state($schema->getRawState());
        }

        if (!is_array($state)) {
            return $this;
        }

        $state = $this->undotFormState($state, $propertyAccessor);

        if (filled($schemaStatePath)) {
            $state = $this->undotFormState([(string) $schemaStatePath => $state], $propertyAccessor);
        }

        foreach ($this->getFormStateWrites($state) as $path => $value) {
            $this->set((string) $path, $value);
        }

        if (!filled($schemaStatePath)) {
            return $this;
        }

        $host = $this->getTestedSchemasHost();

        foreach ($state as $property => $value) {
            $target = $propertyAccessor->get($host, (string) $property);

            if (!is_array($value) || !is_array($target)) {
                continue;
            }

            foreach ($this->getFormStatePrunes($target, $value, (string) $property, (string) $schemaStatePath) as $path => $prunedValue) {
                $this->set((string) $path, $prunedValue);
            }
        }

        return $this;
    }

    /**
     * @param array<string, mixed>|Closure $state
     */
    public function assertFormSet(array|Closure $state, string $form = 'form'): static
    {
        return $this->assertSchemaStateSet($state, $form);
    }

    /**
     * @param array<mixed> $state
     * @return array<string, mixed>
     */
    private function undotFormState(array $state, PropertyAccessor $propertyAccessor): array
    {
        $undotted = [];

        foreach ($state as $path => $value) {
            $propertyAccessor->setInArray($undotted, (string) $path, $value);
        }

        return $undotted;
    }

    /**
     * A non-empty associative array is written key by key, so the keys it leaves out
     * keep their state; anything else is one write, so a list replaces the list it
     * lands on, an empty one included.
     *
     * @param array<mixed> $state
     * @return array<string, mixed>
     */
    private function getFormStateWrites(array $state, string $pathPrefix = ''): array
    {
        $writes = [];

        foreach ($state as $key => $value) {
            $path = ($pathPrefix === '') ? (string) $key : "{$pathPrefix}.{$key}";

            if (is_array($value) && ($value !== []) && !array_is_list($value)) {
                $writes += $this->getFormStateWrites($value, $path);

                continue;
            }

            $writes[$path] = $value;
        }

        return $writes;
    }

    /**
     * Filament's `unsetMissingNumericArrayKeys()`: a numeric key the filled state leaves
     * out is removed, by writing back the array that held it.
     *
     * @param array<mixed> $target
     * @param array<mixed> $state
     * @return array<string, array<mixed>>
     */
    private function getFormStatePrunes(array $target, array $state, string $path, string $schemaStatePath): array
    {
        $prunedTarget = $target;
        $nestedPrunes = [];

        foreach ($target as $key => $value) {
            $keyPath = "{$path}.{$key}";

            if ((is_numeric($key) || array_is_list($state)) && !array_key_exists($key, $state) && str_starts_with($keyPath, $schemaStatePath)) {
                unset($prunedTarget[$key]);

                continue;
            }

            if (is_array($value) && is_array($state[$key] ?? null)) {
                $nestedPrunes += $this->getFormStatePrunes($value, $state[$key], $keyPath, $schemaStatePath);
            }
        }

        if ($prunedTarget === $target) {
            return $nestedPrunes;
        }

        return [$path => $prunedTarget] + $nestedPrunes;
    }
}
