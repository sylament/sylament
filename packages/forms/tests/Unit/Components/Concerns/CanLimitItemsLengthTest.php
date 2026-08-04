<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Sylament\Forms\Tests\Fixtures\Components\FixtureItemsField;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;

/**
 * Wire the field under a `data`-bag host holding `$state` at the field's path, so
 * `getItemsCount()` reads a real raw state slice rather than a detached component.
 *
 * @param array<string, mixed> $data
 */
function wireItemsField(FixtureItemsField $field, array $data = []): FixtureItemsField
{
    $host = new FieldRenderHost();
    $host->data = $data;

    Schema::make($host)->statePath('data')->components([$field])->getComponents();

    return $field;
}

it('has no item limits by default', function (): void {
    $field = FixtureItemsField::make('tags');

    expect($field->getMinItems())->toBeNull()
        ->and($field->getMaxItems())->toBeNull();
});

it('sets the item limits', function (): void {
    $field = FixtureItemsField::make('tags')->minItems(1)->maxItems(3);

    expect($field->getMinItems())->toBe(1)
        ->and($field->getMaxItems())->toBe(3);
});

it('evaluates closure item limits', function (): void {
    $field = FixtureItemsField::make('tags')
        ->minItems(fn (): int => 2)
        ->maxItems(fn (FixtureItemsField $component): int => $component->getMinItems() * 2);

    expect($field->getMinItems())->toBe(2)
        ->and($field->getMaxItems())->toBe(4);
});

it('counts the items in the raw state', function (): void {
    $field = wireItemsField(FixtureItemsField::make('tags'), ['tags' => ['a' => [], 'b' => []]]);

    expect($field->getItemsCount())->toBe(2);
});

it('counts no items when the raw state is not an array', function (): void {
    expect(wireItemsField(FixtureItemsField::make('tags'))->getItemsCount())->toBe(0)
        ->and(wireItemsField(FixtureItemsField::make('tags'), ['tags' => 'nope'])->getItemsCount())->toBe(0);
});
