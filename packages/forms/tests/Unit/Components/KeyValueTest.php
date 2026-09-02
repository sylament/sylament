<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components;

use Sylament\Actions\Action;
use Sylament\Forms\Components\KeyValue;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;

/**
 * Wire a key-value field under a `data`-bag host so it reads and writes a real
 * raw-state slice. The host is returned alongside so a test can read the bag
 * back after a mutation.
 *
 * @param array<string, mixed> $data
 *
 * @return array{KeyValue, FieldRenderHost}
 */
function wireKeyValue(KeyValue $field, array $data = []): array
{
    $host = new FieldRenderHost();
    $host->data = $data;

    Schema::make($host)->statePath('data')->components([$field])->getComponents();

    return [$field, $host];
}

it("ships Filament's defaults", function (): void {
    [$field] = wireKeyValue(KeyValue::make('meta'));

    expect($field->isAddable())->toBeTrue()
        ->and($field->isDeletable())->toBeTrue()
        ->and($field->canEditKeys())->toBeTrue()
        ->and($field->canEditValues())->toBeTrue()
        ->and($field->isReorderable())->toBeFalse()
        ->and($field->getReorderAnimationDuration())->toBe(300)
        ->and($field->getKeyPlaceholder())->toBeNull()
        ->and($field->getValuePlaceholder())->toBeNull();
});

it('carries the shipped literals until a setter names another', function (): void {
    [$field] = wireKeyValue(KeyValue::make('meta'));

    expect($field->getAddActionLabel())->toBe('Add row')
        ->and($field->getDeleteActionLabel())->toBe('Delete row')
        ->and($field->getReorderActionLabel())->toBe('Reorder row')
        ->and($field->getKeyLabel())->toBe('Key')
        ->and($field->getValueLabel())->toBe('Value');

    [$configured] = wireKeyValue(
        KeyValue::make('meta')
            ->addActionLabel('Add attribute')
            ->deleteActionLabel('Remove attribute')
            ->reorderActionLabel('Move attribute')
            ->keyLabel('Attribute')
            ->valueLabel('Setting')
            ->keyPlaceholder('name')
            ->valuePlaceholder('setting'),
    );

    expect($configured->getAddActionLabel())->toBe('Add attribute')
        ->and($configured->getDeleteActionLabel())->toBe('Remove attribute')
        ->and($configured->getReorderActionLabel())->toBe('Move attribute')
        ->and($configured->getKeyLabel())->toBe('Attribute')
        ->and($configured->getValueLabel())->toBe('Setting')
        ->and($configured->getKeyPlaceholder())->toBe('name')
        ->and($configured->getValuePlaceholder())->toBe('setting');
});

it('evaluates every flag closure', function (): void {
    [$field] = wireKeyValue(
        KeyValue::make('meta')
            ->addable(fn (): bool => false)
            ->deletable(fn (): bool => false)
            ->editableKeys(fn (): bool => false)
            ->editableValues(fn (): bool => false)
            ->reorderable(fn (): bool => true),
    );

    expect($field->isAddable())->toBeFalse()
        ->and($field->isDeletable())->toBeFalse()
        ->and($field->canEditKeys())->toBeFalse()
        ->and($field->canEditValues())->toBeFalse()
        ->and($field->isReorderable())->toBeTrue();
});

it('evaluates a label closure against the field', function (): void {
    [$field] = wireKeyValue(
        KeyValue::make('meta')->addActionLabel(fn (KeyValue $component): string => 'Add to ' . $component->getName()),
    );

    expect($field->getAddActionLabel())->toBe('Add to meta');
});

it('pads an empty list with one blank row', function (): void {
    [$field, $host] = wireKeyValue(KeyValue::make('meta'), ['meta' => []]);

    $field->hydrateRows();

    expect($host->data['meta'])->toBe([['key' => '', 'value' => '']]);
});

it('pads a null state with one blank row', function (): void {
    [$field, $host] = wireKeyValue(KeyValue::make('meta'), ['meta' => null]);

    $field->hydrateRows();

    expect($host->data['meta'])->toBe([['key' => '', 'value' => '']]);
});

it('leaves existing rows alone, normalising each into a key/value pair', function (): void {
    [$field, $host] = wireKeyValue(KeyValue::make('meta'), ['meta' => [
        ['key' => 'a', 'value' => 'one'],
        ['value' => 'two'],
        'junk',
    ]]);

    $field->hydrateRows();

    expect($host->data['meta'])->toBe([
        ['key' => 'a', 'value' => 'one'],
        ['key' => null, 'value' => 'two'],
        ['key' => null, 'value' => null],
    ]);
});

it('reads its state as the map the record holds', function (): void {
    [$field] = wireKeyValue(KeyValue::make('meta'), ['meta' => [
        ['key' => 'a', 'value' => 'one'],
        ['key' => '', 'value' => ''],
    ]]);

    expect($field->getState())->toBe(['a' => 'one']);
});

it('writes a map into the raw bag as rows', function (): void {
    [$field, $host] = wireKeyValue(KeyValue::make('meta'));

    $field->state(['a' => 'one', 'b' => 'two']);

    expect($host->data['meta'])->toBe([
        ['key' => 'a', 'value' => 'one'],
        ['key' => 'b', 'value' => 'two'],
    ]);
});

it('hands validation the cast map rather than the raw row list', function (): void {
    [$field] = wireKeyValue(KeyValue::make('meta'));

    expect($field->mutatesStateForValidation())->toBeTrue()
        ->and($field->mutateStateForValidation([['key' => '', 'value' => '']]))->toBe([])
        ->and($field->mutateStateForValidation([['key' => 'a', 'value' => 'one']]))->toBe(['a' => 'one']);
});

it("builds its three actions under Filament's names", function (): void {
    [$field] = wireKeyValue(KeyValue::make('meta')->reorderable());

    expect($field->getAddActionName())->toBe('add')
        ->and($field->getDeleteActionName())->toBe('delete')
        ->and($field->getReorderActionName())->toBe('reorder')
        ->and($field->getAction('add')?->getLabel())->toBe('Add row')
        ->and($field->getAction('delete')?->getLabel())->toBe('Delete row')
        ->and($field->getAction('reorder')?->getLabel())->toBe('Reorder row')
        ->and($field->getAction('delete')?->getIcon())->toBe('heroicons:trash-20-solid')
        ->and($field->getAction('reorder')?->getIcon())->toBe('heroicons:arrows-up-down-20-solid');
});

it('lets a modifier reshape a built action', function (): void {
    [$field] = wireKeyValue(
        KeyValue::make('meta')->addAction(fn (Action $action): Action => $action->label('Add another')),
    );

    expect($field->getAction('add')?->getLabel())->toBe('Add another');
});

it('keeps the built action when a modifier returns nothing', function (): void {
    [$field] = wireKeyValue(
        KeyValue::make('meta')->deleteAction(function (): void {
        }),
    );

    expect($field->getAction('delete')?->getLabel())->toBe('Delete row');
});

it("binds each row's delete trigger to that row", function (): void {
    [$field] = wireKeyValue(KeyValue::make('meta'), ['meta' => [
        ['key' => 'a', 'value' => 'one'],
        ['key' => 'b', 'value' => 'two'],
    ]]);

    $data = $field->getExtraTemplateData();

    expect($data['rows'])->toHaveCount(2)
        ->and($data['rows'][0]['index'])->toBe(0)
        ->and($data['rows'][0]['key'])->toBe('a')
        ->and($data['rows'][1]['value'])->toBe('two')
        ->and($data['rows'][0]['deleteAction']?->getInvokedArguments())->toBe(['row' => 0])
        ->and($data['rows'][1]['deleteAction']?->getInvokedArguments())->toBe(['row' => 1]);
});

it('hands the template every flag the chrome reads', function (): void {
    [$field] = wireKeyValue(KeyValue::make('meta')->reorderable()->reorderAnimationDuration(150));

    $data = $field->getExtraTemplateData();

    expect($data['isAddable'])->toBeTrue()
        ->and($data['isDeletable'])->toBeTrue()
        ->and($data['isReorderable'])->toBeTrue()
        ->and($data['canEditKeys'])->toBeTrue()
        ->and($data['canEditValues'])->toBeTrue()
        ->and($data['animationDuration'])->toBe(150)
        ->and($data['addAction'])->not->toBeNull()
        ->and($data['reorderAction'])->not->toBeNull();
});

it("does not carry Filament's deprecated surface", function (string $method): void {
    expect(method_exists(KeyValue::class, $method))->toBeFalse();
})->with([
    'addButtonLabel',
    'deleteButtonLabel',
    'reorderButtonLabel',
    'disableAddingRows',
    'disableDeletingRows',
    'disableEditingKeys',
    'disableEditingValues',
]);
