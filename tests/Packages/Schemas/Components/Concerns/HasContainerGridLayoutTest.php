<?php

declare(strict_types=1);

use Sylament\Forms\Components\Repeater;

it('answers the single-column defaults until grid() is called, for the whole map and for one breakpoint', function (): void {
    $repeater = Repeater::make('lines');

    expect($repeater->getGridColumns())->toBe(['default' => 1, 'sm' => null, 'md' => null, 'lg' => null, 'xl' => null, '2xl' => null])
        ->and($repeater->getGridColumns('default'))->toBe(1)
        ->and($repeater->getGridColumns('md'))->toBeNull();
});

it('reads a grid() call by the shape of its argument', function (Closure $repeater, array $columns): void {
    expect($repeater()->getGridColumns())->toBe($columns);
})->with([
    'a count, at lg' => [fn (): Repeater => Repeater::make('lines')->grid(3), ['lg' => 3]],
    'counts per breakpoint, merged across calls' => [fn (): Repeater => Repeater::make('lines')->grid(['md' => 2])->grid(['lg' => 4]), ['md' => 2, 'lg' => 4]],
    'a closure answering counts per breakpoint' => [fn (): Repeater => Repeater::make('lines')->grid(static fn (): array => ['sm' => 2]), ['sm' => 2]],
    'a closure answering a count, at lg' => [fn (): Repeater => Repeater::make('lines')->grid(static fn (): int => 5), ['lg' => 5]],
    'an empty breakpoint name dropped and a list entry read at lg' => [fn (): Repeater => Repeater::make('lines')->grid(['' => 2, 3]), ['lg' => 3]],
]);
