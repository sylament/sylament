<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Icon;
use Sylament\Support\Enums\IconSize;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Twig\Markup;

it('draws the icon it is named by', function (): void {
    expect($this->renderSchemaComponent(Icon::make('heroicons:check-circle')))
        ->toContainElement('svg' . Selectors::ICON, 'check-circle');
});

it('draws at the size it is given, a known name as that size, and base or no size at the medium size', function (): void {
    expect($this->renderSchemaComponent(Icon::make('support:star')->size(IconSize::Large)))
        ->toContainElement(Selectors::ICON . SupportSelectors::size('lg'), 'Star')
        ->and($this->renderSchemaComponent(Icon::make('support:star')->size('sm')))
        ->toContainElement(Selectors::ICON . SupportSelectors::size('sm'), 'Star')
        ->and($this->renderSchemaComponent(Icon::make('support:star')->size('base')))
        ->toContainElement(Selectors::ICON . SupportSelectors::MEDIUM, 'Star')
        ->and($this->renderSchemaComponent(Icon::make('support:star')))
        ->toContainElement(Selectors::ICON . SupportSelectors::MEDIUM, 'Star');
});

it('shows a markup tooltip as plain text in its title', function (): void {
    expect($this->renderSchemaComponent(Icon::make('support:star')->tooltip(new Markup('<b>Starred</b>', 'UTF-8'))))
        ->toContainElement(Selectors::ICON . '[title="Starred"]', 'Star');
});
