<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Fieldset;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('marks its label as required when told', function (): void {
    expect($this->renderSchemaComponent(Fieldset::make('Address')->markAsRequired()->components([Text::make('a')])))
        ->toContainElement(Selectors::FIELDSET . ' legend', 'Address*')
        ->toContainElement(Selectors::FIELDSET . ' legend sup' . SupportSelectors::FIELDSET_REQUIRED_MARK, '*');
});

it('marks nothing on its own, as a fieldset is never required itself', function (): void {
    expect($this->renderSchemaComponent(Fieldset::make('Address')->components([Text::make('a')])))
        ->toContainElement(Selectors::FIELDSET . ' legend', 'Address')
        ->not->toContainElement(Selectors::FIELDSET . ' legend sup' . SupportSelectors::FIELDSET_REQUIRED_MARK);
});
