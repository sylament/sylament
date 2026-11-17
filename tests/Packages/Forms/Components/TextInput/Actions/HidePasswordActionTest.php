<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Forms\Selectors;

it('renders a hide-password button its controller answers in the browser, with no live click, drawn with its crossed-eye icon', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('secret')->password()->revealable()))
        ->toContainElement('button[aria-label="Hide password"]' . Selectors::textInputAction('conceal') . ' svg', 'eye-slash-20-solid');
});
