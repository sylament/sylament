<?php

declare(strict_types=1);

use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionSize;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionStatus;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionWeight;
use Sylament\Forms\Tests\Fixtures\Components\FixtureOptionsField;

it('derives labelled options from an enum implementing HasLabel', function (): void {
    expect(FixtureOptionsField::make('status')->options(OptionStatus::class)->getOptions())
        ->toBe(['draft' => 'Draft', 'published' => 'Published']);
});

it('derives options from a backed enum with no label contract', function (): void {
    expect(FixtureOptionsField::make('weight')->options(OptionWeight::class)->getOptions())
        ->toBe([1 => 'Light', 2 => 'Heavy']);
});

it('keys a pure UnitEnum by case name', function (): void {
    expect(FixtureOptionsField::make('size')->options(OptionSize::class)->getOptions())
        ->toBe(['Small' => 'Small', 'Large' => 'Large']);
});

it('derives options from enum() alone', function (): void {
    expect(FixtureOptionsField::make('status')->enum(OptionStatus::class)->getOptions())
        ->toBe(['draft' => 'Draft', 'published' => 'Published']);
});

it('lets an explicit option map win over the enum', function (): void {
    expect(FixtureOptionsField::make('status')->enum(OptionStatus::class)->options(['a' => 'A'])->getOptions())
        ->toBe(['a' => 'A']);
});

it('expands an enum class-string returned by an options closure', function (): void {
    $field = FixtureOptionsField::make('status')->options(fn (): string => OptionStatus::class);

    expect($field->getOptions())->toBe(['draft' => 'Draft', 'published' => 'Published'])
        ->and($field->hasDynamicOptions())->toBeTrue();
});

it('reports an enum-derived option map as static', function (): void {
    expect(FixtureOptionsField::make('status')->options(OptionStatus::class)->hasDynamicOptions())->toBeFalse();
});
