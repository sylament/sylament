<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration\Concerns;

use Sylament\Tables\Tests\Fixtures\Twig\Components\ColumnActionsTable;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The host's column-action live action: what it binds before evaluating a
 * column's closure, and every arm it refuses without effect. The fixture's
 * columns record each call on a `LiveProp`, so what the closure was handed
 * survives the round trip and is readable off the re-hydrated host.
 */

/**
 * @param array<string, mixed> $arguments
 * @return list<string>
 */
function columnActionCalls(array $arguments): array
{
    $host = test()->createLiveComponent(ColumnActionsTable::class)
        ->call('callTableColumnAction', $arguments)
        ->component();

    assert($host instanceof ColumnActionsTable);

    return $host->calls;
}

it('binds the record and its key before evaluating the column closure', function (): void {
    expect(columnActionCalls(['name' => 'title', 'recordKey' => '2']))->toBe(['closure:Let Down:2']);
});

it('refuses a record key the table does not carry', function (): void {
    expect(columnActionCalls(['name' => 'title', 'recordKey' => '99']))->toBe([]);
});

it('refuses a column the table does not declare', function (): void {
    expect(columnActionCalls(['name' => 'nonesuch', 'recordKey' => '1']))->toBe([]);
});

it('refuses a column that carries no action at all', function (): void {
    expect(columnActionCalls(['name' => 'id', 'recordKey' => '1']))->toBe([]);
});

it('evaluates the closure a string action name was wrapped into', function (): void {
    expect(columnActionCalls(['name' => 'album', 'recordKey' => '1']))->toBe(['string:Airbag']);
});
