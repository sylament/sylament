<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;

it('shows the number a badge closure returns, as it shows a number set directly', function (): void {
    $tabs = $this->renderSchemaComponent(Tabs::make('Orders')->tabs([
        Tab::make('Open')->badge(fn (): int => 3),
        Tab::make('Partial')->badge(fn (): float => 2.5),
        Tab::make('Closed')->badge(7),
    ]));

    expect($tabs)->toContainElement('[role="tab"] .sy-badge', '3')
        ->toContainElement('[role="tab"] .sy-badge', '2.5')
        ->toContainElement('[role="tab"] .sy-badge', '7');
});
