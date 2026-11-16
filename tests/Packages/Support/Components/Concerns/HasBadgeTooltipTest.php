<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it("shows a badge tooltip as the badge's title, also one written from the badge, and none untold", function (): void {
    expect($this->renderSchemaComponent(Tabs::make('Orders')->tabs([
        Tab::make('Open')->badge('3')->badgeTooltip('Awaiting dispatch'),
        Tab::make('Late')->badge('2')->badgeTooltip(fn (string $badge): string => "{$badge} overdue"),
        Tab::make('Closed')->badge('5'),
    ])))
        ->toContainElement(Selectors::TAB . ' ' . SupportSelectors::BADGE . '[title="Awaiting dispatch"]', '3')
        ->toContainElement(Selectors::TAB . ' ' . SupportSelectors::BADGE . '[title="2 overdue"]', '2')
        ->toContainElement(Selectors::TAB . ' ' . SupportSelectors::BADGE . ':not([title])', '5');
});
