<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use LogicException;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Records\LineItem;
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
