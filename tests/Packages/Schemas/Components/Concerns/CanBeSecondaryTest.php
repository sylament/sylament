<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('draws its box as a secondary one when told, and not otherwise', function (): void {
    expect($this->renderSchemaComponent(Section::make('Shipping')->secondary()->components([Text::make('Body')])))
        ->toContainElement('section' . SupportSelectors::SECONDARY . ' h2', 'Shipping')
        ->and($this->renderSchemaComponent(Section::make('Shipping')->secondary(false)->components([Text::make('Body')])))
        ->toContainElement('section:not(' . SupportSelectors::SECONDARY . ') h2', 'Shipping')
        ->and($this->renderSchemaComponent(Section::make('Shipping')->components([Text::make('Body')])))
        ->toContainElement('section:not(' . SupportSelectors::SECONDARY . ') h2', 'Shipping');
});
