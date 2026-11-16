<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('packs its box compactly when told, and not otherwise', function (): void {
    expect($this->renderSchemaComponent(Section::make('Shipping')->compact()->components([Text::make('Body')])))
        ->toContainElement('section' . SupportSelectors::COMPACT . ' h2', 'Shipping')
        ->and($this->renderSchemaComponent(Section::make('Shipping')->compact(false)->components([Text::make('Body')])))
        ->toContainElement('section:not(' . SupportSelectors::COMPACT . ') h2', 'Shipping')
        ->and($this->renderSchemaComponent(Section::make('Shipping')->components([Text::make('Body')])))
        ->toContainElement('section:not(' . SupportSelectors::COMPACT . ') h2', 'Shipping');
});
