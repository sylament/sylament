<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use LogicException;
use Sylament\Actions\Tests\Fixtures\Concerns\RecordResolutionFixture;
use Sylament\Actions\Tests\Fixtures\Concerns\SchemaActionHost;
use Sylament\Schemas\Contracts\InteractsWithSchemas;

it('mounts and auto-runs a direct schema child action from the schemaComponent context', function (): void {
    $host = new SchemaActionHost();

    expect($host->mountAction('publish', [], ['schemaComponent' => 'schema']))->toBe('published')
        ->and($host->published)->toBe('published')
        ->and($host->mountedActions)->toBe([]);
});

it('resolves a component-registered action through a nested container key', function (): void {
    $host = new SchemaActionHost();

    expect($host->mountAction('archive', [], ['schemaComponent' => 'schema.box::section']))->toBe('archived');
});

it('no-ops the mount when the schema name is unknown', function (): void {
    $host = new SchemaActionHost();

    expect($host->mountAction('publish', [], ['schemaComponent' => 'missing']))->toBeNull()
        ->and($host->published)->toBeNull()
        ->and($host->mountedActions)->toBe([]);
});

it('resolves a schema declared by a parameterless Schema-returning method', function (): void {
    $host = new SchemaActionHost();

    expect($host->mountAction('ping', [], ['schemaComponent' => 'sidebar']))->toBe('pong')
        ->and($host->mountedActions)->toBe([]);
});

it('no-ops the mount when the schema name resolves to a non-schema-shaped live-action method', function (): void {
    $host = new SchemaActionHost();

    expect($host->mountAction('publish', [], ['schemaComponent' => 'unmountAction']))->toBeNull()
        ->and($host->published)->toBeNull()
        ->and($host->mountedActions)->toBe([]);
});

it('no-ops the mount when the schema name resolves to callMountedAction', function (): void {
    $host = new SchemaActionHost();

    expect($host->mountAction('publish', [], ['schemaComponent' => 'callMountedAction']))->toBeNull()
        ->and($host->published)->toBeNull()
        ->and($host->mountedActions)->toBe([]);
});

it('no-ops the mount when the schema name resolves to a method whose first parameter is not a Schema', function (): void {
    $host = new SchemaActionHost();

    expect($host->mountAction('publish', [], ['schemaComponent' => 'mountAction']))->toBeNull()
        ->and($host->published)->toBeNull()
        ->and($host->mountedActions)->toBe([]);
});

it('never invokes a parameterless host domain method named by the schemaComponent context', function (): void {
    $host = new SchemaActionHost();

    expect($host->mountAction('publish', [], ['schemaComponent' => 'purge']))->toBeNull()
        ->and($host->purged)->toBeFalse()
        ->and($host->mountedActions)->toBe([]);
});

it('throws when a schemaComponent context reaches a host without the schemas contract', function (): void {
    $fixture = new RecordResolutionFixture();

    expect(fn (): mixed => $fixture->mountAction('x', [], ['schemaComponent' => 'schema']))
        ->toThrow(LogicException::class, InteractsWithSchemas::class);
});
