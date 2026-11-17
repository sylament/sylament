<?php

declare(strict_types=1);

namespace Sylament\Forms\Testing;

use Closure;
use LogicException;
use PHPUnit\Framework\Assert;
use Sylament\Actions\Testing\Testable;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
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

        foreach ($this->getFormStateWrites($state, (string) $schemaStatePath) as $path => $value) {
            $this->set((string) $path, $value);
        }

        if (!filled($schemaStatePath)) {
            return $this;
        }

        $state = $this->undotFormState([(string) $schemaStatePath => $state], $propertyAccessor);

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
     * Each key names a field relative to the form's state path: alone it asserts the
     * field has an error, and with a value the class of a constraint the field failed or
     * one of its messages. The errors are those of the component that answered the last
     * request; after a redirect there are none, since no validation failed.
     *
     * @param array<int|string, string> $keys
     */
    public function assertHasFormErrors(array $keys = [], ?string $form = null): static
    {
        Assert::assertFalse($this->response()->isRedirection(), 'Component has no errors.');

        [$host, $schema] = $this->getFormErrorsHostAndSchema($form);

        Assert::assertTrue($this->getFormErrors($host, $schema) !== [], 'Component has no errors.');

        foreach ($keys as $key => $value) {
            if (is_int($key)) {
                $path = $this->getFormErrorPath($schema, $value);

                Assert::assertTrue($host->getErrorsForSchemaStatePath($path) !== [], "Component missing error: {$path}");

                continue;
            }

            $path = $this->getFormErrorPath($schema, $key);

            Assert::assertContains(
                $value,
                [...$host->getErrorConstraintsForSchemaStatePath($path), ...$host->getErrorsForSchemaStatePath($path)],
                "Component has no matching failed rule or error message [{$value}] for [{$path}] attribute.",
            );
        }

        return $this;
    }

    /**
     * With no keys it asserts the form has no error at all; otherwise each key is read as
     * {@see assertHasFormErrors()} reads it. A redirect passes, since no validation failed.
     *
     * @param array<int|string, string> $keys
     */
    public function assertHasNoFormErrors(array $keys = [], ?string $form = null): static
    {
        if ($this->response()->isRedirection()) {
            return $this;
        }

        [$host, $schema] = $this->getFormErrorsHostAndSchema($form);

        if ($keys === []) {
            $errors = $this->getFormErrors($host, $schema);

            Assert::assertTrue($errors === [], 'Component has errors: ' . implode(', ', array_map(
                static fn (string $path, array $messages): string => '"' . $path . '" => ["' . implode('", "', $messages) . '"]',
                array_keys($errors),
                $errors,
            )));

            return $this;
        }

        foreach ($keys as $key => $value) {
            if (is_int($key)) {
                $path = $this->getFormErrorPath($schema, $value);

                Assert::assertFalse($host->getErrorsForSchemaStatePath($path) !== [], "Component has error: {$path}");

                continue;
            }

            $path = $this->getFormErrorPath($schema, $key);

            Assert::assertNotContains(
                $value,
                [...$host->getErrorConstraintsForSchemaStatePath($path), ...$host->getErrorsForSchemaStatePath($path)],
                "Component has [{$value}] errors for [{$path}] attribute.",
            );
        }

        return $this;
    }

    /**
     * The form is resolved on the re-hydrated host, as `fillForm()` resolves it, and its
     * errors are read off the host that answered the last request.
     *
     * @return array{InteractsWithSchemas, Schema}
     */
    private function getFormErrorsHostAndSchema(?string $form): array
    {
        $form = $this->resolveTestedSchemaName($form);

        $this->assertSchemaExists($form);

        $schema = $this->getTestedSchema($this->getTestedSchemasHost(), $form);
        $host = $this->getRespondingComponent();

        if (!$host instanceof InteractsWithSchemas) {
            throw new LogicException(sprintf('The [%s] component holds no schemas: it does not implement [%s].', $host::class, InteractsWithSchemas::class));
        }

        return [$host, $schema];
    }

    private function getFormErrorPath(Schema $schema, string $key): string
    {
        $schemaStatePath = $schema->getStatePath();

        return filled($schemaStatePath) ? "{$schemaStatePath}.{$key}" : $key;
    }

    /**
     * Every error the form shows, by state path: under a component's own path, a path a
     * component constrains, or the form's own path, where a record violation no
     * component maps is filed.
     *
     * @return array<string, list<string>>
     */
    private function getFormErrors(InteractsWithSchemas $host, Schema $schema): array
    {
        $paths = [(string) $schema->getStatePath(), ...array_keys($schema->getValidationConstraints())];

        foreach ($schema->getFlatComponents(withActions: false, withHidden: true) as $component) {
            $paths[] = $component->getStatePath();
        }

        $errors = [];

        foreach (array_unique(array_filter($paths, is_string(...))) as $path) {
            $messages = $host->getErrorsForSchemaStatePath($path);

            if ($messages !== []) {
                $errors[$path] = $messages;
            }
        }

        return $errors;
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
     * lands on, an empty one included. The prefix is only a path, so a state path
     * ending at a list index never turns the state it holds into one list write.
     *
     * @param array<mixed> $state
     * @return array<string, mixed>
     */
    private function getFormStateWrites(array $state, string $pathPrefix = ''): array
    {
        if (($state === []) && ($pathPrefix !== '')) {
            return [$pathPrefix => $state];
        }

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
