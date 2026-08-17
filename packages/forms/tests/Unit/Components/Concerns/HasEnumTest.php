<?php

declare(strict_types=1);

use Sylament\Forms\Components\Select;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionSize;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionStatus;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionWeight;
use Sylament\Forms\Tests\Fixtures\Components\FixtureField;
use Sylament\Forms\Tests\Fixtures\Components\FixtureOptionsField;
use Sylament\Schemas\State\Casts\EnumArrayStateCast;
use Sylament\Schemas\State\Casts\EnumStateCast;

it('stores and evaluates the enum on any field', function (): void {
    expect(FixtureField::make('status')->getEnum())->toBeNull()
        ->and(FixtureField::make('status')->enum(OptionStatus::class)->getEnum())->toBe(OptionStatus::class)
        ->and(FixtureField::make('status')->enum(fn (): string => OptionWeight::class)->getEnum())->toBe(OptionWeight::class)
        ->and(FixtureField::make('status')->enum(OptionStatus::class)->enum(null)->getEnum())->toBeNull();
});

it('sets the enum from an options() class-string', function (): void {
    expect(FixtureOptionsField::make('status')->options(OptionStatus::class)->getEnum())->toBe(OptionStatus::class);
});

it('leaves a non-enum literal string as an options value', function (): void {
    $field = FixtureOptionsField::make('status')->options('date');

    expect($field->getEnum())->toBeNull()
        ->and($field->getOptions())->toBe([]);
});

it('appends no state cast without an enum', function (): void {
    $field = FixtureField::make('status');

    expect($field->getEnumDefaultStateCast())->toBeNull()
        ->and($field->getDefaultStateCasts())->toBe([]);
});

it('appends the enum state cast to a fields default casts', function (): void {
    $field = FixtureField::make('status')->enum(OptionStatus::class);
    $casts = $field->getDefaultStateCasts();

    expect($field->getEnumDefaultStateCast())->toBeInstanceOf(EnumStateCast::class)
        ->and($casts)->toHaveCount(1)
        ->and($casts[0])->toBeInstanceOf(EnumStateCast::class);
});

it('refuses an enum with no backing value', function (): void {
    FixtureField::make('status')->enum(OptionSize::class)->getEnumDefaultStateCast();
})->throws(LogicException::class, 'not a backed enum');

it('swaps the enum cast for its array form on a multiple select', function (): void {
    expect(Select::make('tags')->enum(OptionStatus::class)->getEnumDefaultStateCast())
        ->toBeInstanceOf(EnumStateCast::class)
        ->and(Select::make('tags')->multiple()->enum(OptionStatus::class)->getEnumDefaultStateCast())
        ->toBeInstanceOf(EnumArrayStateCast::class);
});

it('lets an enum win over the selects own option casts', function (): void {
    $casts = Select::make('status')->options(OptionStatus::class)->getDefaultStateCasts();

    expect($casts)->toHaveCount(1)
        ->and($casts[0])->toBeInstanceOf(EnumStateCast::class);
});

it('keeps the enum cast when a custom state cast is added', function (): void {
    $field = Select::make('status')->options(OptionStatus::class)->stateCast(new EnumStateCast(OptionWeight::class));

    expect($field->getDefaultStateCasts())->toHaveCount(1)
        ->and($field->getDefaultStateCasts()[0])->toBeInstanceOf(EnumStateCast::class)
        ->and($field->getStateCasts())->toHaveCount(2);
});

it('reports nullable boolean state on Select only', function (): void {
    expect(FixtureField::make('status')->hasNullableBooleanState())->toBeFalse()
        ->and(Select::make('status')->hasNullableBooleanState())->toBeTrue();
});
