<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration;

use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

uses(InteractsWithLiveComponents::class);

/*
 * `fixIndistinctState()` through a real round trip: the hook only runs from
 * schemas' `#[PostHydrate] callAfterSchemaStateUpdated()`, so nothing short of a
 * live component fires it. Item keys are read back off the mounted bag rather
 * than written into the test — a mount re-keys every item by uuid.
 */

/**
 * @param array<int, array<string, mixed>> $rows
 *
 * @return array{TestLiveComponent, list<string>}
 */
function mountIndistinctRows(array $rows, bool $isFixed = true): array
{
    $component = test()->createLiveComponent('IndistinctRepeaterForm', [
        'data' => ['rows' => $rows],
        'isFixed' => $isFixed,
    ]);

    /** @var array<string, mixed> $items */
    $items = $component->component()->data['rows'];

    return [$component, array_map(strval(...), array_keys($items))];
}

it('clears a scalar value a sibling item already held', function (): void {
    [$component, $keys] = mountIndistinctRows([['topic' => 'a'], ['topic' => null]]);

    $component->set("data.rows.{$keys[1]}.topic", 'a');

    expect($component->component()->data['rows'][$keys[0]]['topic'])->toBeNull()
        ->and($component->component()->data['rows'][$keys[1]]['topic'])->toBe('a');
});

it('leaves a sibling holding a different value alone', function (): void {
    [$component, $keys] = mountIndistinctRows([['topic' => 'b'], ['topic' => null]]);

    $component->set("data.rows.{$keys[1]}.topic", 'a');

    expect($component->component()->data['rows'][$keys[0]]['topic'])->toBe('b');
});

it('unticks a sibling checkbox rather than nulling it', function (): void {
    [$component, $keys] = mountIndistinctRows([['primary' => true], ['primary' => false]]);

    $component->set("data.rows.{$keys[1]}.primary", true);

    expect($component->component()->data['rows'][$keys[0]]['primary'])->toBeFalse()
        ->and($component->component()->data['rows'][$keys[1]]['primary'])->toBeTrue();
});

it('leaves an unticked sibling alone when the subject is unticked', function (): void {
    [$component, $keys] = mountIndistinctRows([['primary' => false], ['primary' => true]]);

    $component->set("data.rows.{$keys[1]}.primary", false);

    expect($component->component()->data['rows'][$keys[0]]['primary'])->toBeFalse()
        ->and($component->component()->data['rows'][$keys[1]]['primary'])->toBeFalse();
});

it('subtracts the picked values from a sibling selection and reindexes it', function (): void {
    [$component, $keys] = mountIndistinctRows([['roles' => ['a', 'b']], ['roles' => []]]);

    $component->set("data.rows.{$keys[1]}.roles", ['a']);

    expect($component->component()->data['rows'][$keys[0]]['roles'])->toBe(['b'])
        ->and($component->component()->data['rows'][$keys[1]]['roles'])->toBe(['a']);
});

it('cascades the array arm forward through a third item in one pass', function (): void {
    [$component, $keys] = mountIndistinctRows([
        ['roles' => []],
        ['roles' => ['a', 'b']],
        ['roles' => ['b', 'c']],
    ]);

    $component->set("data.rows.{$keys[0]}.roles", ['a']);

    // Item 0's pick takes 'a' off item 1, leaving ['b']; our hooks fire for every
    // component whose raw state changed, so item 1's own hook runs later in the same
    // pass and takes 'b' off item 2. Filament fires only for the updated path and
    // would leave item 2 holding ['b', 'c'].
    expect($component->component()->data['rows'][$keys[0]]['roles'])->toBe(['a'])
        ->and($component->component()->data['rows'][$keys[1]]['roles'])->toBe(['b'])
        ->and($component->component()->data['rows'][$keys[2]]['roles'])->toBe(['c']);
});

it('does not cascade back into an item the walk has already passed', function (): void {
    [$component, $keys] = mountIndistinctRows([
        ['roles' => ['a', 'b']],
        ['roles' => ['b', 'c']],
        ['roles' => []],
    ]);

    $component->set("data.rows.{$keys[2]}.roles", ['a']);

    // The same repair in the other direction: item 0 loses 'a', but its own hook sits
    // before item 2's in the flat component order and has already been considered, so
    // item 1 keeps the 'b' a forward cascade would have taken.
    expect($component->component()->data['rows'][$keys[0]]['roles'])->toBe(['b'])
        ->and($component->component()->data['rows'][$keys[1]]['roles'])->toBe(['b', 'c'])
        ->and($component->component()->data['rows'][$keys[2]]['roles'])->toBe(['a']);
});

it('leaves the sibling that held a value alone when this item clears its own', function (): void {
    // The `blank()` guard returns here, but nothing observes it: a blank subject
    // matches no filled sibling, so the arms below would leave the same bag.
    [$component, $keys] = mountIndistinctRows([['topic' => 'a'], ['topic' => 'a']]);

    $component->set("data.rows.{$keys[1]}.topic", null);

    expect($component->component()->data['rows'][$keys[0]]['topic'])->toBe('a');
});

it('repaints the corrected sibling in the rendered control', function (): void {
    [$component, $keys] = mountIndistinctRows([['topic' => 'a', 'primary' => true], ['topic' => null, 'primary' => false]]);

    $component->set("data.rows.{$keys[1]}.topic", 'a');
    $component->set("data.rows.{$keys[1]}.primary", true);

    $crawler = $component->render()->crawler();

    expect($crawler->filter("select[data-model$=\"data.rows.{$keys[0]}.topic\"] option[selected]")->count())->toBe(0)
        ->and($crawler->filter("select[data-model$=\"data.rows.{$keys[1]}.topic\"] option[selected]")->attr('value'))->toBe('a')
        ->and($crawler->filter("input[data-model$=\"data.rows.{$keys[0]}.primary\"]")->attr('checked'))->toBeNull()
        ->and($crawler->filter("input[data-model$=\"data.rows.{$keys[1]}.primary\"]")->attr('checked'))->not->toBeNull();
});

it('does nothing under a false condition', function (): void {
    [$component, $keys] = mountIndistinctRows([['topic' => 'a'], ['topic' => null]], isFixed: false);

    $component->set("data.rows.{$keys[1]}.topic", 'a');

    expect($component->component()->data['rows'][$keys[0]]['topic'])->toBe('a');
});

it('does nothing for a field outside any repeater', function (): void {
    [$component, $keys] = mountIndistinctRows([['topic' => 'a'], ['topic' => 'a']]);

    $component->set('data.loose', 'a');

    expect($component->component()->data['loose'])->toBe('a')
        ->and($component->component()->data['rows'][$keys[0]]['topic'])->toBe('a')
        ->and($component->component()->data['rows'][$keys[1]]['topic'])->toBe('a');
});

it('clears an enum-backed value a sibling item already held', function (): void {
    [$component, $keys] = mountIndistinctRows([['status' => 'draft'], ['status' => null]]);

    $component->set("data.rows.{$keys[1]}.status", 'draft');

    expect($component->component()->data['rows'][$keys[0]]['status'])->toBeNull()
        ->and($component->component()->data['rows'][$keys[1]]['status'])->toBe('draft');
});
