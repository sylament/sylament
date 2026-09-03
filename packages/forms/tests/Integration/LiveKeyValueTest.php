<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration;

use LogicException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The key-value field's three structural actions through a real round-trip. The
 * rows are the wire shape and their indexes are what every per-cell binding and
 * every trigger names, so each case here asserts the list the round-trip left
 * behind rather than the map the field answers.
 */

/**
 * The mount payload a key-value trigger emits: the action's name, its arguments
 * and the field's absolute key as the schema-component context.
 *
 * @param array<string, mixed> $arguments
 *
 * @return array{name: string, arguments: array<string, mixed>, context: array<string, string>}
 */
function keyValueMount(string $name, array $arguments = []): array
{
    return [
        'name' => $name,
        'arguments' => $arguments,
        'context' => ['schemaComponent' => 'schema.meta'],
    ];
}

it('rests on one blank row, so the first keystroke names a path the server rendered', function (): void {
    expect($this->createLiveComponent('KeyValueForm')->component()->data)
        ->toBe(['meta' => [['key' => '', 'value' => '']]]);
});

it('appends a blank row on the add action', function (): void {
    $component = $this->createLiveComponent('KeyValueForm', ['data' => ['meta' => [['key' => 'a', 'value' => 'one']]]])
        ->call('mountAction', keyValueMount('add'));

    expect($component->component()->data['meta'])->toBe([
        ['key' => 'a', 'value' => 'one'],
        ['key' => '', 'value' => ''],
    ]);
});

it('removes exactly the mounted row and renumbers the survivors', function (): void {
    $component = $this->createLiveComponent('KeyValueForm', [
        'data' => ['meta' => [['key' => 'a', 'value' => 'one'], ['key' => 'b', 'value' => 'two'], ['key' => 'c', 'value' => 'three']]],
    ])->call('mountAction', keyValueMount('delete', ['row' => 1]));

    expect($component->component()->data['meta'])->toBe([
        ['key' => 'a', 'value' => 'one'],
        ['key' => 'c', 'value' => 'three'],
    ]);
});

it('re-pads with the blank row when the last row is deleted', function (): void {
    $component = $this->createLiveComponent('KeyValueForm', ['data' => ['meta' => [['key' => 'a', 'value' => 'one']]]])
        ->call('mountAction', keyValueMount('delete', ['row' => 0]));

    expect($component->component()->data['meta'])->toBe([['key' => '', 'value' => '']]);
});

it('keeps the dirty cell writes the delete request carried, at the index they land on', function (): void {
    $component = $this->createLiveComponent('KeyValueForm', [
        'data' => ['meta' => [['key' => 'a', 'value' => 'one'], ['key' => 'b', 'value' => 'two']]],
    ])
        ->set('data.meta.1.value', 'x')
        ->call('mountAction', keyValueMount('delete', ['row' => 0]));

    expect($component->component()->data['meta'])->toBe([['key' => 'b', 'value' => 'x']]);
});

it('refuses a delete naming no row of the list', function (array $arguments): void {
    $this->createLiveComponent('KeyValueForm', ['data' => ['meta' => [['key' => 'a', 'value' => 'one']]]])
        ->call('mountAction', keyValueMount('delete', $arguments));
})->with([
    'missing' => [[]],
    'null' => [['row' => null]],
    'non-numeric' => [['row' => 'first']],
    'out of range' => [['row' => 4]],
])->throws(LogicException::class, 'The key-value delete action was mounted without a "row" argument naming the row.');

it('takes a numeric-string row index, which is what a JSON round trip makes of one', function (): void {
    $component = $this->createLiveComponent('KeyValueForm', [
        'data' => ['meta' => [['key' => 'a', 'value' => 'one'], ['key' => 'b', 'value' => 'two']]],
    ])->call('mountAction', keyValueMount('delete', ['row' => '0']));

    expect($component->component()->data['meta'])->toBe([['key' => 'b', 'value' => 'two']]);
});

it('permutes the list into the order the reorder action was handed', function (): void {
    $component = $this->createLiveComponent('KeyValueForm', [
        'data' => ['meta' => [['key' => 'a', 'value' => 'one'], ['key' => 'b', 'value' => 'two'], ['key' => 'c', 'value' => 'three']]],
    ])->call('mountAction', keyValueMount('reorder', ['items' => ['2', '0', '1']]));

    expect($component->component()->data['meta'])->toBe([
        ['key' => 'c', 'value' => 'three'],
        ['key' => 'a', 'value' => 'one'],
        ['key' => 'b', 'value' => 'two'],
    ]);
});

it('drops an index the list does not hold and appends the ones the order omitted, in their old order', function (): void {
    $component = $this->createLiveComponent('KeyValueForm', [
        'data' => ['meta' => [['key' => 'a', 'value' => 'one'], ['key' => 'b', 'value' => 'two'], ['key' => 'c', 'value' => 'three']]],
    ])->call('mountAction', keyValueMount('reorder', ['items' => ['2', '9']]));

    expect($component->component()->data['meta'])->toBe([
        ['key' => 'c', 'value' => 'three'],
        ['key' => 'a', 'value' => 'one'],
        ['key' => 'b', 'value' => 'two'],
    ]);
});

it('refuses a reorder that is not a list of indexes', function (mixed $items): void {
    $this->createLiveComponent('KeyValueForm', ['data' => ['meta' => [['key' => 'a', 'value' => 'one']]]])
        ->call('mountAction', keyValueMount('reorder', ['items' => $items]));
})->with([
    'null' => [null],
    'a string' => ['0,1'],
    'a map' => [['first' => '0']],
])->throws(LogicException::class, 'The key-value reorder action was mounted without an "items" argument listing the row indexes in their new order.');

it('round-trips a row list the client wrote whole, and answers the map it encodes', function (): void {
    $component = $this->createLiveComponent('KeyValueForm')
        ->set('data.meta', [['key' => 'a', 'value' => 'one'], ['key' => '', 'value' => '']]);

    expect($component->component()->data['meta'])->toBe([
        ['key' => 'a', 'value' => 'one'],
        ['key' => '', 'value' => ''],
    ]);

    // Neither the state casts nor `afterStateHydrated` run on a live request, so
    // the bag stays exactly the row list the client wrote.
    expect($component->component()->getSchema()->getState())->toBe(['meta' => ['a' => 'one']]);

    $rows = $component->render()->crawler()->filter('tbody tr');

    expect($rows)->toHaveCount(2)
        ->and($rows->eq(0)->filter('input')->eq(0)->attr('value'))->toBe('a')
        ->and($rows->eq(1)->filter('input')->eq(0)->attr('value'))->toBe('');
});

it('hands the state hooks the map rather than the row list', function (): void {
    $component = $this->createLiveComponent('KeyValueForm', ['data' => ['meta' => [['key' => 'a', 'value' => 'one']]]])
        ->call('mountAction', keyValueMount('add'));

    expect($component->component()->stateUpdates)->toBe(1)
        ->and($component->component()->lastState)->toBe(['a' => 'one']);
});
