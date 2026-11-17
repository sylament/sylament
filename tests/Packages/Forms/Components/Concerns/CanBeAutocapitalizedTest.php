<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;

it('renders the autocapitalize attribute the setter maps', function (Closure $field, string $selector): void {
    expect($this->renderSchemaComponent($field()))->toContainElement($selector);
})->with([
    'on' => [static fn (): TextInput => TextInput::make('name')->autocapitalize(), 'input[name="name"][autocapitalize="on"]'],
    'off' => [static fn (): TextInput => TextInput::make('name')->autocapitalize(false), 'input[name="name"][autocapitalize="off"]'],
    'a mode' => [static fn (): TextInput => TextInput::make('name')->autocapitalize('words'), 'input[name="name"][autocapitalize="words"]'],
    'unset' => [static fn (): TextInput => TextInput::make('name'), 'input[name="name"]:not([autocapitalize])'],
]);
