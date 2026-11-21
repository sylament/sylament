<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\Translation\TranslatableMessage;

it('shows the number a badge closure returns, as it shows a number set directly', function (): void {
    $tabs = $this->renderSchemaComponent(Tabs::make('Orders')->tabs([
        Tab::make('Open')->badge(fn (): int => 3),
        Tab::make('Partial')->badge(fn (): float => 2.5),
        Tab::make('Closed')->badge(7),
    ]));

    expect($tabs)->toContainElement(Selectors::TAB . ' ' . SupportSelectors::BADGE, '3')
        ->toContainElement(Selectors::TAB . ' ' . SupportSelectors::BADGE, '2.5')
        ->toContainElement(Selectors::TAB . ' ' . SupportSelectors::BADGE, '7');
});

it('paints a badge in the colour it is given or picks from the badge', function (): void {
    expect($this->renderSchemaComponent(Tabs::make('Orders')->tabs([
        Tab::make('Open')->badge('3')->badgeColor('success'),
        Tab::make('Late')->badge('0')->badgeColor(fn (string $badge): string => $badge === '0' ? 'warning' : 'danger'),
    ])))
        ->toContainElement(Selectors::TAB . ' ' . SupportSelectors::BADGE . SupportSelectors::color('success'), '3')
        ->toContainElement(Selectors::TAB . ' ' . SupportSelectors::BADGE . SupportSelectors::color('warning'), '0');
});

it('translates a badge given as a message, and shows a number as the number it is', function (): void {
    expect($this->renderSchemaComponent(Tabs::make('Orders')->tabs([
        Tab::make('Open')->badge(new TranslatableMessage('plain', [], 'fixtures-support')),
        Tab::make('Closed')->badge(7),
    ])))
        ->toContainElement(Selectors::TAB . ' ' . SupportSelectors::BADGE, 'Plain')
        ->toContainElement(Selectors::TAB . ' ' . SupportSelectors::BADGE, '7');
});
