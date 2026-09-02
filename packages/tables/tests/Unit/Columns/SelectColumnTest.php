<?php

declare(strict_types=1);

use Sylament\Tables\Columns\Contracts\Editable;
use Sylament\Tables\Columns\SelectColumn;
use Sylament\Tables\Tests\Fixtures\Records\Song;

it('is editable, click-disabled and placeholder-selectable out of the box', function (): void {
    $column = SelectColumn::make('mood');

    expect($column)->toBeInstanceOf(Editable::class)
        ->and($column->isClickDisabled())->toBeTrue()
        ->and($column->canSelectPlaceholder())->toBeTrue()
        ->and($column->getPlaceholder())->toBe('Select an option')
        ->and($column->getOptions())->toBe([]);
});

it('carries no state casts, which is what keeps the option traits usable', function (): void {
    expect(SelectColumn::make('mood')->getStateCasts())->toBe([]);
});

it('reads an option map, an enum and a closure', function (): void {
    expect(SelectColumn::make('mood')->options(['calm' => 'Calm'])->getOptions())->toBe(['calm' => 'Calm'])
        ->and(SelectColumn::make('mood')->options(fn (): array => ['wild' => 'Wild'])->getOptions())->toBe(['wild' => 'Wild']);
});

it('re-reads a closure option map per call unless the options are remembered', function (): void {
    $calls = 0;
    $options = function () use (&$calls): array {
        $calls++;

        return ['calm' => 'Calm'];
    };

    $fresh = SelectColumn::make('mood')->options($options);
    $fresh->getOptions();
    $fresh->getOptions();

    expect($calls)->toBe(2);

    $calls = 0;
    $remembered = SelectColumn::make('mood')->options($options)->rememberOptions();
    $remembered->getOptions();
    $remembered->getOptions();

    expect($calls)->toBe(1)
        ->and($remembered->areOptionsRemembered())->toBeTrue();
});

it('flattens grouped options and drops the disabled ones from the enabled set', function (): void {
    $column = SelectColumn::make('mood')
        ->options(['Quiet' => ['calm' => 'Calm'], 'Loud' => ['wild' => 'Wild']])
        ->disableOptionWhen(fn (mixed $value): bool => $value === 'wild');

    expect($column->getEnabledOptions())->toBe(['calm' => 'Calm'])
        ->and($column->isOptionDisabled('wild', 'Wild'))->toBeTrue()
        ->and($column->hasDisabledOptions())->toBeTrue();
});

it('normalises the comparison state the option template selects by', function (): void {
    $column = SelectColumn::make('mood')
        ->options(['calm' => 'Calm'])
        ->record(new Song('Airbag', 'OK Computer'))
        ->state('calm');

    expect($column->getOptionComparisonState())->toBe('calm');
});

it('drops the placeholder option when selectablePlaceholder is off', function (): void {
    expect(SelectColumn::make('mood')->selectablePlaceholder(false)->canSelectPlaceholder())->toBeFalse();
});
