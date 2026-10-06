<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use LogicException;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Records\Checklist;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * The record binding's proof: a repeater bound to a plain-array record property, filled
 * off the record and mapped back onto it through the ordinary walks. The record
 * half is what the item pipeline exists for — the live bag keys items so a
 * mutation never renumbers a sibling, the record sees a positional list — so
 * every case here goes through fill()/getData() rather than reading state.
 *
 * Items are positionally keyed (`Repeater::fake()`) wherever a test names an
 * item key: a per-request uuid cannot be written into an assertion, and the
 * record walks normalise both key shapes identically.
 */

/**
 * Bind a repeater to a record instance through a `data`-bag host, keyed and
 * validator-fed so `getData()` can run its validation pass.
 *
 * @return array{Schema, FieldRenderHost}
 */
function wireRecordRepeater(Repeater $repeater, object $record): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components([$repeater]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

it('fills its items off the record and maps them back positionally', function (): void {
    Repeater::fake();

    $record = new Checklist();
    $record->tags = [['name' => 'a']];

    $repeater = Repeater::make('tags')->components([TextInput::make('name')]);
    [$schema, $host] = wireRecordRepeater($repeater, $record);

    $schema->fill($record);

    expect($host->data['tags'])->toBe([['name' => 'a']]);

    $repeater->getAction($repeater->getAddActionName())?->call();

    $host->data['tags'][1]['name'] = 'b';

    expect($schema->getData())->toBe($record)
        ->and($record->tags)->toBe([['name' => 'a'], ['name' => 'b']]);
});

it('maps an empty repeater onto an empty list', function (): void {
    Repeater::fake();

    $record = new Checklist();
    $record->tags = [['name' => 'a']];

    [$schema, $host] = wireRecordRepeater(Repeater::make('tags')->components([TextInput::make('name')]), $record);

    $schema->fill($record);

    $host->data['tags'] = [];

    expect($schema->getData())->toBe($record)
        ->and($record->tags)->toBe([]);
});

it('nulls an empty repeater declared nullWhenBlank', function (): void {
    Repeater::fake();

    $record = new Checklist();
    $record->tags = [['name' => 'a']];

    [$schema, $host] = wireRecordRepeater(
        Repeater::make('tags')->nullWhenBlank()->components([TextInput::make('name')]),
        $record,
    );

    $schema->fill($record);

    $host->data['tags'] = [];

    expect($schema->getData())->toBe($record)
        ->and($record->tags)->toBeNull();
});

it('keeps an all-blank item alive under nullWhenBlank', function (): void {
    Repeater::fake();

    $record = new Checklist();

    [$schema, $host] = wireRecordRepeater(
        Repeater::make('tags')->nullWhenBlank()->components([TextInput::make('name')]),
        $record,
    );

    $schema->fill($record);

    $host->data['tags'] = [['name' => null]];

    $schema->getData();

    expect($record->tags)->toBe([['name' => null]]);
});

it('refuses nullWhenBlank over a record property that cannot hold null', function (): void {
    Repeater::fake();

    $record = new Checklist();

    [$schema] = wireRecordRepeater(
        Repeater::make('steps')->nullWhenBlank()->components([TextInput::make('name')]),
        $record,
    );

    $schema->fill($record);

    expect(fn (): object => $schema->getData())
        ->toThrow(LogicException::class, 'the record declares that property non-nullable');
});

it('validates each item field at its own absolute path', function (): void {
    Repeater::fake();

    $record = new Checklist();
    $record->tags = [['name' => 'a'], ['name' => null]];

    $repeater = Repeater::make('tags')->components([TextInput::make('name')->required()]);
    [$schema, $host] = wireRecordRepeater($repeater, $record);

    $schema->fill($record);

    expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
        ->and($host->getErrorsForSchemaStatePath('data.tags.1.name'))->toBe(['This value should not be blank.'])
        ->and($host->getErrorsForSchemaStatePath('data.tags.0.name'))->toBe([])
        ->and($host->data['tags'][0]['name'])->toBe('a')
        ->and($record->tags)->toBe([['name' => 'a'], ['name' => null]]);

    $repeater->getAction($repeater->getDeleteActionName())?->__invoke(['item' => '1'])->call();

    expect($schema->getData())->toBe($record)
        ->and($record->tags)->toBe([['name' => 'a']]);
});

it('round-trips a scalar list through simple mode', function (): void {
    Repeater::fake();

    $record = new Checklist();
    $record->tags = ['x', 'y'];

    [$schema, $host] = wireRecordRepeater(Repeater::make('tags')->simple(TextInput::make('value')), $record);

    $schema->fill($record);

    expect($host->data['tags'])->toBe([['value' => 'x'], ['value' => 'y']]);

    $host->data['tags'][1]['value'] = 'z';

    expect($schema->getData())->toBe($record)
        ->and($record->tags)->toBe(['x', 'z']);
});

it('round-trips a nested repeater positional at both levels', function (): void {
    $record = new Checklist();
    $record->tags = [
        ['name' => 'a', 'notes' => [['note' => 'first'], ['note' => 'second']]],
        ['name' => 'b', 'notes' => []],
    ];

    [$schema, $host] = wireRecordRepeater(
        Repeater::make('tags')->components([
            TextInput::make('name'),
            Repeater::make('notes')->components([TextInput::make('note')]),
        ]),
        $record,
    );

    $schema->fill($record);

    $outerKeys = array_keys($host->data['tags']);
    $innerKeys = array_keys($host->data['tags'][$outerKeys[0]]['notes']);

    $host->data['tags'][$outerKeys[0]]['notes'][$innerKeys[1]]['note'] = 'edited';

    expect($schema->getData())->toBe($record)
        ->and($record->tags)->toBe([
            ['name' => 'a', 'notes' => [['note' => 'first'], ['note' => 'edited']]],
            ['name' => 'b', 'notes' => []],
        ]);
});

it('carries an item key no field declares back onto the record', function (): void {
    Repeater::fake();

    $record = new Checklist();
    $record->tags = [['name' => 'a', 'legacy' => true]];

    [$schema, $host] = wireRecordRepeater(Repeater::make('tags')->components([TextInput::make('name')]), $record);

    $schema->fill($record);

    $host->data['tags'][0]['name'] = 'edited';

    expect($schema->getData())->toBe($record)
        ->and($record->tags)->toBe([['name' => 'edited', 'legacy' => true]]);
});
