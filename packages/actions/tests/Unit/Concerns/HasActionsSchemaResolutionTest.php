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

it('throws when a schemaComponent context reaches a host without the schemas contract', function (): void {
    $fixture = new RecordResolutionFixture();

    expect(fn (): mixed => $fixture->mountAction('x', [], ['schemaComponent' => 'schema']))
        ->toThrow(LogicException::class, InteractsWithSchemas::class);
});
