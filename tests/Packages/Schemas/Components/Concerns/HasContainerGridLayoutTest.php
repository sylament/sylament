<?php

declare(strict_types=1);

use Sylament\Forms\Components\Repeater;

it('answers the single-column defaults until grid() is called, for the whole map and for one breakpoint', function (): void {
    $repeater = Repeater::make('lines');

    expect($repeater->getGridColumns())->toBe(['default' => 1, 'sm' => null, 'md' => null, 'lg' => null, 'xl' => null, '2xl' => null])
        ->and($repeater->getGridColumns('default'))->toBe(1)
        ->and($repeater->getGridColumns('md'))->toBeNull();
});

it('drops a breakpoint with an empty name from the map', function (): void {
    expect(Repeater::make('lines')->grid(['' => 2, 'md' => 3])->getGridColumns())->toBe(['md' => 3]);
});
