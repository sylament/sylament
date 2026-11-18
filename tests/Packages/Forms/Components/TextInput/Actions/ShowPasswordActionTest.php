<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Forms\Selectors;

it('renders a show-password button its controller answers in the browser, with no live click, drawn with its eye icon', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('secret')->password()->revealable()))
        ->toContainElement(Selectors::SHOW_PASSWORD_BUTTON . Selectors::textInputAction('reveal') . ' svg', 'eye-20-solid');
});
