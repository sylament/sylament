<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('shows the number a badge closure returns, as it shows a number set directly', function (): void {
    $tabs = $this->renderSchemaComponent(Tabs::make('Orders')->tabs([
        Tab::make('Open')->badge(fn (): int => 3),
        Tab::make('Partial')->badge(fn (): float => 2.5),
        Tab::make('Closed')->badge(7),
    ]));

    expect($tabs)->toContainElement('[role="tab"] ' . SupportSelectors::BADGE, '3')
        ->toContainElement('[role="tab"] ' . SupportSelectors::BADGE, '2.5')
        ->toContainElement('[role="tab"] ' . SupportSelectors::BADGE, '7');
});

it('paints a badge in the colour it is given or picks from the badge', function (): void {
    expect($this->renderSchemaComponent(Tabs::make('Orders')->tabs([
        Tab::make('Open')->badge('3')->badgeColor('success'),
        Tab::make('Late')->badge('0')->badgeColor(fn (string $badge): string => $badge === '0' ? 'warning' : 'danger'),
    ])))
        ->toContainElement('[role="tab"] ' . SupportSelectors::BADGE . SupportSelectors::color('success'), '3')
        ->toContainElement('[role="tab"] ' . SupportSelectors::BADGE . SupportSelectors::color('warning'), '0');
});
