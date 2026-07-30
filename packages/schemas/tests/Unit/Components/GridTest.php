<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Schemas\Components\Grid;

it('defaults to two columns from the lg breakpoint', function (): void {
    expect(Grid::make()->getAllColumns())->toBe(['lg' => 2]);
});

it('wraps an int into the lg breakpoint', function (): void {
    expect(Grid::make(3)->getAllColumns())->toBe(['lg' => 3]);
});

it('takes a responsive map verbatim', function (): void {
    expect(Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])->getAllColumns())
        ->toBe(['default' => 1, 'md' => 2, 'xl' => 4]);
});

it('resolves a column closure through the real Invoker', function (): void {
    expect(Grid::make(fn (): int => 6)->getAllColumns())->toBe(['lg' => 6]);
});

it('passes the grid to configuration closures as $component', function (): void {
    $received = null;

    $grid = Grid::make(2)->extraAttributes(function (Grid $component) use (&$received): array {
        $received = $component;

        return [];
    });

    $grid->getExtraAttributes();

    expect($received)->toBe($grid);
});

it('applies configureUsing() closures on make()', function (): void {
    Grid::configureUsing(function (Grid $grid): void {
        $grid->columns(4);
    });

    expect(Grid::make()->getAllColumns())->toBe(['lg' => 4]);
});
