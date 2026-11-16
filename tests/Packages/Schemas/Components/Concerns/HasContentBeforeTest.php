<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('puts its content before its header text when told, and not otherwise', function (): void {
    expect($this->renderSchemaComponent(Section::make('Shipping')->contentBefore()->components([Text::make('Body')])))
        ->toContainElement('section' . SupportSelectors::CONTENT_BEFORE . ' h2', 'Shipping')
        ->and($this->renderSchemaComponent(Section::make('Shipping')->contentBefore(false)->components([Text::make('Body')])))
        ->toContainElement('section:not(' . SupportSelectors::CONTENT_BEFORE . ') h2', 'Shipping')
        ->and($this->renderSchemaComponent(Section::make('Shipping')->components([Text::make('Body')])))
        ->toContainElement('section:not(' . SupportSelectors::CONTENT_BEFORE . ') h2', 'Shipping');
});
