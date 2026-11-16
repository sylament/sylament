<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Text;
use Sylament\Support\Enums\Size;
use Sylament\Support\Enums\TextSize;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Twig\Markup;

it('shows its content as a piece of text of its own, text as text and markup as markup', function (): void {
    $text = $this->renderSchemaComponent(Text::make('<b>Total</b>'));

    expect($text)->toContainElement(Selectors::TEXT . SupportSelectors::STANDALONE_ROOT, '<b>Total</b>')
        ->and($text->filter(Selectors::TEXT . ' b'))->toHaveCount(0)
        ->and($this->renderSchemaComponent(Text::make(new Markup('<b>Total</b>', 'UTF-8'))))
        ->toContainElement(Selectors::TEXT . ' b', 'Total');
});

it('sets its size when given one, a known name as that size and any other name as a class of its own', function (): void {
    expect($this->renderSchemaComponent(Text::make('Total')->size(TextSize::Large)))
        ->toContainElement(Selectors::TEXT . SupportSelectors::size('lg'), 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')->size(Size::ExtraSmall)))
        ->toContainElement(Selectors::TEXT . SupportSelectors::size('xs'), 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')->size('sm')))
        ->toContainElement(Selectors::TEXT . SupportSelectors::size('sm'), 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')->size('tiny')))
        ->toContainElement(Selectors::TEXT . '.tiny' . SupportSelectors::UNSIZED, 'Total')
        ->and($this->renderSchemaComponent(Text::make('Total')->size(' ')))
        ->toContainElement(Selectors::TEXT . SupportSelectors::UNSIZED, 'Total');
});
