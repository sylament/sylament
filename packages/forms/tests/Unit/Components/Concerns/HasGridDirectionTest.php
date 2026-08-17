<?php

declare(strict_types=1);

use Sylament\Forms\Tests\Fixtures\Components\FixtureOptionsField;
use Sylament\Support\Enums\GridDirection;

it('has no grid direction by default', function (): void {
    expect(FixtureOptionsField::make('status')->getGridDirection())->toBeNull();
});

it('keeps a grid direction instance', function (): void {
    expect(FixtureOptionsField::make('status')->gridDirection(GridDirection::Row)->getGridDirection())
        ->toBe(GridDirection::Row);
});

it('coerces a grid direction string to the enum', function (): void {
    expect(FixtureOptionsField::make('status')->gridDirection('row')->getGridDirection())->toBe(GridDirection::Row)
        ->and(FixtureOptionsField::make('status')->gridDirection('column')->getGridDirection())->toBe(GridDirection::Column);
});

it('evaluates a grid direction closure', function (): void {
    expect(FixtureOptionsField::make('status')->gridDirection(fn (): string => 'column')->getGridDirection())
        ->toBe(GridDirection::Column);
});

it('answers null for a string naming no direction', function (): void {
    expect(FixtureOptionsField::make('status')->gridDirection('diagonal')->getGridDirection())->toBeNull()
        ->and(FixtureOptionsField::make('status')->gridDirection('')->getGridDirection())->toBeNull();
});

it('answers null once the grid direction is unset again', function (): void {
    expect(FixtureOptionsField::make('status')->gridDirection(GridDirection::Row)->gridDirection(null)->getGridDirection())
        ->toBeNull();
});
