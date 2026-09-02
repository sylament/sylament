<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components\Concerns;

use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Components\Field;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionStatus;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;

/*
 * The predicate half of the sibling concerns: a pure read, so it needs the
 * repeater-item rig rather than a live round trip.
 */

/**
 * The field of one seeded repeater item, wired into a real schema over a bag the
 * items are read from — positionally keyed, so a test can name the item it wants.
 *
 * @param array<int, array<string, mixed>> $items
 */
function itemFieldAt(Field $field, array $items, int $index): Field
{
    $host = new FieldRenderHost();
    $host->data = ['rows' => $items];

    $repeater = Repeater::make('rows')->components([$field]);
    Schema::make($host)->statePath('data')->components([$repeater])->getComponents();

    $components = array_values($repeater->getItems())[$index]->getComponents();

    return $components[0] instanceof Field ? $components[0] : $field;
}

it('disables an option a sibling item holds', function (): void {
    $field = itemFieldAt(
        Select::make('topic')->options(['a' => 'A', 'b' => 'B'])->disableOptionsWhenSelectedInSiblingRepeaterItems(),
        [['topic' => 'a'], ['topic' => null]],
        1,
    );

    expect($field->isOptionDisabled('a', 'A'))->toBeTrue()
        ->and($field->isOptionDisabled('b', 'B'))->toBeFalse();
});

it('leaves an option this item also holds enabled', function (): void {
    $field = itemFieldAt(
        Select::make('topic')->options(['a' => 'A', 'b' => 'B'])->disableOptionsWhenSelectedInSiblingRepeaterItems(),
        [['topic' => 'a'], ['topic' => 'a']],
        1,
    );

    expect($field->isOptionDisabled('a', 'A'))->toBeFalse();
});

it('leaves every option enabled when the siblings are blank', function (): void {
    $field = itemFieldAt(
        Select::make('topic')->options(['a' => 'A', 'b' => 'B'])->disableOptionsWhenSelectedInSiblingRepeaterItems(),
        [['topic' => null], ['topic' => '']],
        1,
    );

    expect($field->isOptionDisabled('a', 'A'))->toBeFalse()
        ->and($field->isOptionDisabled('b', 'B'))->toBeFalse();
});

it('matches an enum sibling by its backing value', function (): void {
    $field = itemFieldAt(
        Select::make('topic')->options(OptionStatus::class)->disableOptionsWhenSelectedInSiblingRepeaterItems(),
        [['topic' => OptionStatus::Draft], ['topic' => null]],
        1,
    );

    expect($field->isOptionDisabled('draft', 'Draft'))->toBeTrue()
        ->and($field->isOptionDisabled('published', 'Published'))->toBeFalse();
});

it('removes every value a sibling selection holds for an array-valued field', function (): void {
    $field = itemFieldAt(
        CheckboxList::make('roles')->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])->disableOptionsWhenSelectedInSiblingRepeaterItems(),
        [['roles' => ['a', 'b']], ['roles' => ['b']]],
        1,
    );

    // 'b' is this item's own selection, so it stays clickable; 'a' is the sibling's alone.
    expect($field->isOptionDisabled('a', 'A'))->toBeTrue()
        ->and($field->isOptionDisabled('b', 'B'))->toBeFalse()
        ->and($field->isOptionDisabled('c', 'C'))->toBeFalse();
});

it('disables an int-keyed option a sibling holds as the submitted string', function (): void {
    // The option map's keys are PHP array keys, so `'1'` is the int 1, while the
    // sibling's value came off the wire as the string '1'. A strict comparison
    // without the string normalisation would leave every int-keyed map undisabled.
    $field = itemFieldAt(
        Select::make('topic')->options([1 => 'One', 2 => 'Two'])->disableOptionsWhenSelectedInSiblingRepeaterItems(),
        [['topic' => '1'], ['topic' => null]],
        1,
    );

    expect($field->isOptionDisabled(1, 'One'))->toBeTrue()
        ->and($field->isOptionDisabled(2, 'Two'))->toBeFalse();
});

it('disables nothing outside a repeater', function (): void {
    $field = Select::make('topic')->options(['a' => 'A'])->disableOptionsWhenSelectedInSiblingRepeaterItems();

    Schema::make(new FieldRenderHost())->statePath('data')->components([$field])->getComponents();

    expect($field->isOptionDisabled('a', 'A'))->toBeFalse();
});
