<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;

it('renders the autocomplete attribute the setter maps', function (Closure $field, string $selector): void {
    expect($this->renderSchemaComponent($field()))->toContainElement($selector);
})->with([
    'on' => [static fn (): TextInput => TextInput::make('name')->autocomplete(), 'input[name="name"][autocomplete="on"]'],
    'off' => [static fn (): TextInput => TextInput::make('name')->autocomplete(false), 'input[name="name"][autocomplete="off"]'],
    'a token' => [static fn (): TextInput => TextInput::make('name')->autocomplete('given-name'), 'input[name="name"][autocomplete="given-name"]'],
    'unset' => [static fn (): TextInput => TextInput::make('name'), 'input[name="name"]:not([autocomplete])'],
]);
