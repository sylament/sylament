<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use LogicException;
use Sylament\Actions\Tests\Fixtures\Concerns\RecordResolutionFixture;

it('throws when a recordKey is present but no resolveRecordUsing callback is registered', function (): void {
    $fixture = new RecordResolutionFixture();
    $fixture->mountedActions = [[
        'name' => 'x',
        'arguments' => [],
        'context' => ['recordKey' => 1],
    ]];

    expect(fn (): mixed => $fixture->exposeMountedActionRecord())->toThrow(LogicException::class);
});

it('resolves the record when resolveRecordUsing is registered', function (): void {
    $fixture = new RecordResolutionFixture();
    $fixture->resolveRecordUsing(fn (mixed $key): string => "row-{$key}");
    $fixture->mountedActions = [[
        'name' => 'x',
        'arguments' => [],
        'context' => ['recordKey' => 1],
    ]];

    expect($fixture->exposeMountedActionRecord())->toBe('row-1');
});

it('returns null when the context has no recordKey', function (): void {
    $fixture = new RecordResolutionFixture();
    $fixture->mountedActions = [[
        'name' => 'x',
        'arguments' => [],
        'context' => [],
    ]];

    expect($fixture->exposeMountedActionRecord())->toBeNull();
});
