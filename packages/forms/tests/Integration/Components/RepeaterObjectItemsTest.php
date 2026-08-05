<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use LogicException;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Records\CastChecklist;
use Sylament\Forms\Tests\Fixtures\Records\GroupItem;
use Sylament\Forms\Tests\Fixtures\Records\LineItem;
use Sylament\Forms\Tests\Fixtures\Records\StrictLineItem;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\ObjectStateCast;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * The value-object item mode: a repeater whose items dehydrate as instances of
 * a declared class and fill back as value-shaped arrays. This file starts at the
 * declaration surface — how itemsAs() builds its cast and which compositions it
 * refuses — before the pipeline cases that run the state through the record.
 */

/**
 * Bind a repeater to a record instance through a `data`-bag host, keyed and
 * validator-fed so `getData()` can run its validation pass.
 *
 * @return array{Schema, FieldRenderHost}
 */
function wireObjectItemsRepeater(Repeater $repeater, object $record): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components([$repeater]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

it('builds the item cast from a class-string declaration', function (): void {
    $repeater = Repeater::make('lines')->itemsAs(LineItem::class);

    expect($repeater->getItemCast())->toBeInstanceOf(ObjectStateCast::class)
        ->and($repeater->getItemCast()?->getItemClass())->toBe(LineItem::class);
});

it('takes a prebuilt cast instance as-is', function (): void {
    $cast = new ObjectStateCast(LineItem::class);

    expect(Repeater::make('lines')->itemsAs($cast)->getItemCast())->toBe($cast);
});

it('evaluates a closure declaration to either arm', function (): void {
    $repeater = Repeater::make('lines')->itemsAs(static fn (): string => LineItem::class);

    expect($repeater->getItemCast()?->getItemClass())->toBe(LineItem::class);
});

it('clears the declaration with null', function (): void {
    expect(Repeater::make('lines')->itemsAs(LineItem::class)->itemsAs(null)->getItemCast())->toBeNull();
});

it('refuses closures beside a prebuilt cast instance', function (): void {
    expect(fn (): Repeater => Repeater::make('lines')->itemsAs(
        new ObjectStateCast(LineItem::class),
        denormalizeUsing: static fn (array $item): LineItem => new LineItem(),
    ))->toThrow(LogicException::class, 'both conversion halves');
});

it('refuses itemsAs over a simple repeater, naming the leaf-cast path', function (): void {
    expect(fn (): Repeater => Repeater::make('lines')->simple(TextInput::make('value'))->itemsAs(LineItem::class))
        ->toThrow(LogicException::class, 'stateCast');
});

it('refuses simple over an itemsAs repeater, naming the leaf-cast path', function (): void {
    expect(fn (): Repeater => Repeater::make('lines')->itemsAs(LineItem::class)->simple(TextInput::make('value')))
        ->toThrow(LogicException::class, 'stateCast');
});

it('fills array items off a DTO list and maps fresh DTOs back positionally', function (): void {
    Repeater::fake();

    $record = new CastChecklist();
    $record->lines = [new LineItem(title: 'a', note: 'first')];

    $repeater = Repeater::make('lines')->itemsAs(LineItem::class)->components([
        TextInput::make('title'),
        TextInput::make('note'),
    ]);
    [$schema, $host] = wireObjectItemsRepeater($repeater, $record);

    $schema->fill($record);

    expect($host->data['lines'])->toBe([['title' => 'a', 'note' => 'first']]);

    $repeater->getAction($repeater->getAddActionName())?->call();

    $host->data['lines'][1]['title'] = 'b';

    $original = $record->lines[0];

    expect($schema->getData())->toBe($record)
        ->and($record->lines)->toHaveCount(2)
        ->and($record->lines[0])->toBeInstanceOf(LineItem::class)
        ->and($record->lines[0])->not->toBe($original)
        ->and($record->lines[0]->title)->toBe('a')
        ->and($record->lines[1]->title)->toBe('b')
        ->and($record->lines[1]->note)->toBeNull()
        ->and(array_keys($record->lines))->toBe([0, 1]);
});

it('keeps the live bag, item state and old state as arrays under the cast', function (): void {
    Repeater::fake();

    $record = new CastChecklist();
    $record->lines = [new LineItem(title: 'a')];

    $repeater = Repeater::make('lines')->itemsAs(LineItem::class)->components([TextInput::make('title')]);
    [$schema, $host] = wireObjectItemsRepeater($repeater, $record);

    $schema->fill($record);

    $host->persistOldState();

    expect($repeater->getState())->toBe([['title' => 'a', 'note' => null]])
        ->and($repeater->getOldState())->toBe([['title' => 'a', 'note' => null]])
        ->and($repeater->getItemState('0'))->toBe(['title' => 'a', 'note' => null])
        ->and($repeater->getRawItemState('0'))->toBe(['title' => 'a', 'note' => null]);
});

it('drops an unmanaged item key at construction instead of carrying it', function (): void {
    Repeater::fake();

    $record = new CastChecklist();
    $record->lines = [new LineItem(title: 'a')];

    [$schema, $host] = wireObjectItemsRepeater(
        Repeater::make('lines')->itemsAs(LineItem::class)->components([TextInput::make('title')]),
        $record,
    );

    $schema->fill($record);

    $host->data['lines'][0]['tampered'] = 'x';

    $schema->getData();

    expect($record->lines[0])->toBeInstanceOf(LineItem::class)
        ->and(get_object_vars($record->lines[0]))->toBe(['title' => 'a', 'note' => null]);
});

it('nulls an empty cast repeater declared nullWhenBlank and keeps a filled one', function (): void {
    Repeater::fake();

    $record = new CastChecklist();
    $record->lines = [new LineItem(title: 'a')];

    [$schema, $host] = wireObjectItemsRepeater(
        Repeater::make('lines')->itemsAs(LineItem::class)->nullWhenBlank()->components([TextInput::make('title')]),
        $record,
    );

    $schema->fill($record);

    $host->data['lines'] = [];

    expect($schema->getData())->toBe($record)
        ->and($record->lines)->toBeNull();
});

it('validates item fields against the raw arrays, per path', function (): void {
    Repeater::fake();

    $record = new CastChecklist();
    $record->lines = [new LineItem()];

    [$schema, $host] = wireObjectItemsRepeater(
        Repeater::make('lines')->itemsAs(LineItem::class)->components([TextInput::make('title')->required()]),
        $record,
    );

    $schema->fill($record);

    expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
        ->and($host->getErrorsForSchemaStatePath('data.lines.0.title'))->toBe(['This value should not be blank.']);
});

it('names the required() fix when an unvalidated blank meets a strict constructor', function (): void {
    Repeater::fake();

    $record = new CastChecklist();

    [$schema, $host] = wireObjectItemsRepeater(
        Repeater::make('lines')->itemsAs(StrictLineItem::class)->components([TextInput::make('title')]),
        $record,
    );

    $schema->fill($record);

    $host->data['lines'] = [['title' => null]];

    expect(fn (): object => $schema->getData())->toThrow(LogicException::class, 'required()');
});

it('round-trips a cast inner repeater under a plain outer one', function (): void {
    $record = new CastChecklist();
    $record->groups = [['name' => 'g1', 'lines' => [new LineItem(title: 'inner')]]];

    [$schema, $host] = wireObjectItemsRepeater(
        Repeater::make('groups')->components([
            TextInput::make('name'),
            Repeater::make('lines')->itemsAs(LineItem::class)->components([TextInput::make('title')]),
        ]),
        $record,
    );

    $schema->fill($record);

    $outerKeys = array_keys($host->data['groups']);
    $innerKeys = array_keys($host->data['groups'][$outerKeys[0]]['lines']);

    expect($host->data['groups'][$outerKeys[0]]['lines'][$innerKeys[0]])->toBe(['title' => 'inner', 'note' => null]);

    $host->data['groups'][$outerKeys[0]]['lines'][$innerKeys[0]]['title'] = 'edited';

    expect($schema->getData())->toBe($record)
        ->and($record->groups[0]['name'])->toBe('g1')
        ->and($record->groups[0]['lines'][0])->toBeInstanceOf(LineItem::class)
        ->and($record->groups[0]['lines'][0]->title)->toBe('edited');
});

it('round-trips a cast outer repeater over plain inner items', function (): void {
    $record = new CastChecklist();
    $record->objectGroups = [new GroupItem(name: 'g1', lines: [['title' => 'inner']])];

    [$schema, $host] = wireObjectItemsRepeater(
        Repeater::make('objectGroups')->itemsAs(GroupItem::class)->components([
            TextInput::make('name'),
            Repeater::make('lines')->components([TextInput::make('title')]),
        ]),
        $record,
    );

    $schema->fill($record);

    $outerKeys = array_keys($host->data['objectGroups']);
    $innerKeys = array_keys($host->data['objectGroups'][$outerKeys[0]]['lines']);

    expect($host->data['objectGroups'][$outerKeys[0]]['lines'][$innerKeys[0]])->toBe(['title' => 'inner']);

    $host->data['objectGroups'][$outerKeys[0]]['lines'][$innerKeys[0]]['title'] = 'edited';

    expect($schema->getData())->toBe($record)
        ->and($record->objectGroups[0])->toBeInstanceOf(GroupItem::class)
        ->and($record->objectGroups[0]->name)->toBe('g1')
        ->and($record->objectGroups[0]->lines)->toBe([['title' => 'edited']]);
});

it('round-trips a cast outer repeater over cast inner items', function (): void {
    $record = new CastChecklist();
    $record->objectGroups = [new GroupItem(name: 'g1', lines: [new LineItem(title: 'inner')])];

    [$schema, $host] = wireObjectItemsRepeater(
        Repeater::make('objectGroups')->itemsAs(GroupItem::class)->components([
            TextInput::make('name'),
            Repeater::make('lines')->itemsAs(LineItem::class)->components([TextInput::make('title')]),
        ]),
        $record,
    );

    $schema->fill($record);

    $outerKeys = array_keys($host->data['objectGroups']);
    $innerKeys = array_keys($host->data['objectGroups'][$outerKeys[0]]['lines']);

    expect($host->data['objectGroups'][$outerKeys[0]]['lines'][$innerKeys[0]])->toBe(['title' => 'inner', 'note' => null]);

    $host->data['objectGroups'][$outerKeys[0]]['lines'][$innerKeys[0]]['title'] = 'edited';

    expect($schema->getData())->toBe($record)
        ->and($record->objectGroups[0])->toBeInstanceOf(GroupItem::class)
        ->and($record->objectGroups[0]->name)->toBe('g1')
        ->and($record->objectGroups[0]->lines[0])->toBeInstanceOf(LineItem::class)
        ->and($record->objectGroups[0]->lines[0]->title)->toBe('edited');
});

it('leaves a hidden cast repeater unconstructed and unmapped', function (): void {
    Repeater::fake();

    $record = new CastChecklist();
    $record->lines = [new LineItem(title: 'a')];

    [$schema] = wireObjectItemsRepeater(
        Repeater::make('lines')->itemsAs(LineItem::class)->hidden()->components([TextInput::make('title')]),
        $record,
    );

    $original = $record->lines[0];

    $schema->fill($record);
    $schema->getData();

    expect($record->lines)->toHaveCount(1)
        ->and($record->lines[0])->toBe($original)
        ->and($record->lines[0]->title)->toBe('a');
});
