<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;

it('autofocuses its control when told, and not when the condition is false', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('name')->autofocus()))
        ->toContainElement('input[name="name"][autofocus]')
        ->and($this->renderSchemaComponent(TextInput::make('name')->autofocus(false)))
        ->toContainElement('input[name="name"]:not([autofocus])');
});
