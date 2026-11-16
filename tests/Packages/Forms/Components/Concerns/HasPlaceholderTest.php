<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;

it('shows its placeholder in the empty control, and none when it is empty', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('email')->placeholder('you@example.com')))
        ->toContainElement('input[name="email"][placeholder="you@example.com"]')
        ->and($this->renderSchemaComponent(TextInput::make('email')->placeholder('')))
        ->toContainElement('input[name="email"]:not([placeholder])');
});
