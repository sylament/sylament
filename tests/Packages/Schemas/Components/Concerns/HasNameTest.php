<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Schemas\Selectors;

it('names its control by the name it was made with or given, apart from its state path, and by none once it is taken away', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('email')))
        ->toContainElement('input[name="email"]' . Selectors::boundTo('data.email'))
        ->and($this->renderSchemaComponent(TextInput::make('email')->name('contact')))
        ->toContainElement('input[name="contact"]' . Selectors::boundTo('data.email'))
        ->and($this->renderSchemaComponent(TextInput::make('email')->name(null)))
        ->toContainElement('input' . Selectors::boundTo('data.email') . ':not([name])')
        ->not->toContainElement('input[name]');
});
