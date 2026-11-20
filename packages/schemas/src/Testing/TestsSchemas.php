<?php

declare(strict_types=1);

namespace Sylament\Schemas\Testing;

use Closure;
use LogicException;
use PHPUnit\Framework\Assert;
use Sylament\Actions\Testing\Testable;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;

use function Sylament\Support\filled;

/**
 * @phpstan-require-extends Testable
 */
trait TestsSchemas
{
    public function assertSchemaExists(string $name): static
    {
        $host = $this->component();
        $schema = ($host instanceof InteractsWithSchemas) ? $host->resolveSchemaByName($name) : null;
        $hostClass = $host::class;

        Assert::assertInstanceOf(
            Schema::class,
            $schema,
            "Failed asserting that a schema with the name [{$name}] exists on the [{$hostClass}] component.",
        );

        return $this;
    }

    /**
     * @param array<string, mixed>|Closure $state
     */
    public function assertSchemaStateSet(array|Closure $state, ?string $schema = null): static
    {
        $schema = $this->resolveTestedSchemaName($schema);

        $this->assertSchemaExists($schema);

        $host = $this->getTestedSchemasHost();
        $schemaInstance = $this->getTestedSchema($host, $schema);
        $schemaStatePath = $schemaInstance->getStatePath();

        if ($state instanceof Closure) {
            $state = $state($schemaInstance->getRawState());
        }

        if (!is_array($state)) {
            return $this;
        }

        $hostClass = $host::class;

        foreach ($state as $key => $value) {
            $component = $schemaInstance->getComponent((string) $key, withActions: false, withHidden: true);

            if ($component instanceof Component) {
                Assert::assertEquals(
                    $value,
                    $component->getState(),
                    "Failed asserting that a component [{$key}] has the expected state in the [{$schema}] schema on the [{$hostClass}] component.",
                );

                continue;
            }

            Assert::assertEquals($value, $schemaInstance->getPropertyAccessor()->get($host, (filled($schemaStatePath) ? "{$schemaStatePath}." : '') . $key));
        }

        return $this;
    }

    protected function getDefaultTestingSchemaName(): ?string
    {
        $host = $this->component();

        return ($host instanceof InteractsWithSchemas) ? $host->getDefaultTestingSchemaName() : null;
    }

    protected function resolveTestedSchemaName(?string $schema): string
    {
        $schema ??= $this->getMountedActionSchemaName() ?? $this->getDefaultTestingSchemaName();

        return $schema ?? throw new LogicException(sprintf('The [%s] component declares no schema to test.', $this->component()::class));
    }

    protected function getTestedSchemasHost(): InteractsWithSchemas
    {
        $host = $this->component();

        if (!$host instanceof InteractsWithSchemas) {
            throw new LogicException(sprintf('The [%s] component holds no schemas: it does not implement [%s].', $host::class, InteractsWithSchemas::class));
        }

        return $host;
    }

    protected function getTestedSchema(InteractsWithSchemas $host, string $name): Schema
    {
        return $host->resolveSchemaByName($name)
            ?? throw new LogicException(sprintf('The [%s] component has no [%s] schema.', $host::class, $name));
    }
}
