<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use LogicException;
use Sylament\Actions\Tests\Fixtures\Concerns\SchemalessSchemaHostFixture;
use Sylament\Schemas\Contracts\InteractsWithSchemas;

/*
 * The host-shape guard `getMountedActionSchema()` carries: a schema-bearing
 * action has nowhere to build its schema on a host that is not an
 * `InteractsWithSchemas`, which is a wiring bug and must say so rather than
 * silently mounting a modal with no fields. The schema-less control pins that
 * the guard is about the *action's* schema, not about the host.
 */

it('refuses to build a mounted action schema on a host that carries no schemas', function (): void {
    $host = new SchemalessSchemaHostFixture();

    expect(fn (): mixed => $host->mountAction('edit'))
        ->toThrow(LogicException::class, InteractsWithSchemas::class);
});

it('mounts a schema-less action on a host that carries no schemas', function (): void {
    $host = new SchemalessSchemaHostFixture();
    $host->mountAction('note');

    expect($host->mountedActions)->toHaveCount(1)
        ->and($host->mountedActionsData)->toBe([0 => []]);
});
