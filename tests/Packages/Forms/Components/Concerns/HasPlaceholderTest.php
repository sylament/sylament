<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;

use function Symfony\Component\Translation\t;

it('shows its placeholder in the empty control, and none when it is empty', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('email')->placeholder('you@example.com')))
        ->toContainElement('input[name="email"][placeholder="you@example.com"]')
        ->and($this->renderSchemaComponent(TextInput::make('email')->placeholder('')))
        ->toContainElement('input[name="email"]:not([placeholder])');
});

it('shows a placeholder given as a message, translated', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('plant')->placeholder(t('field.placeholder', [], 'fixtures-forms'))))
        ->toContainElement('input[name="plant"][placeholder="Type a plant (translated)"]');
});
