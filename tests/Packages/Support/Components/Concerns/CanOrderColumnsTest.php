<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('takes its place in the grid order at each breakpoint it names, a number at lg, a later call adding to the earlier', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->columnOrder(['default' => 1, 'md' => 2])->columnOrder(3)))
        ->toContainElement(SupportSelectors::columnOrder('default', 1) . SupportSelectors::columnOrder('md', 2) . SupportSelectors::columnOrder('lg', 3), 'Total');
});

it('takes the order a closure answers at the default breakpoint alone', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->columnOrder(fn (): int => 3)))
        ->toContainElement(SupportSelectors::columnOrder('default', 3) . ':not([style*="--col-order-lg"])', 'Total');
});
