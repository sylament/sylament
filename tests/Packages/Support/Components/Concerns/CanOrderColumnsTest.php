<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Text;

it('takes its place in the grid order at each breakpoint it names, a number at lg, a later call adding to the earlier', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->columnOrder(['default' => 1, 'md' => 2])->columnOrder(3)))
        ->toContainElement('[style*="--col-order-default: 1"][style*="--col-order-md: 2"][style*="--col-order-lg: 3"]', 'Total');
});

it('takes the order a closure answers at the default breakpoint alone', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->columnOrder(fn (): int => 3)))
        ->toContainElement('[style*="--col-order-default: 3"]:not([style*="--col-order-lg"])', 'Total');
});
