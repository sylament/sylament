<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;

it('renders the step the setter declares, and none when unset', function (Closure $field, string $selector): void {
    expect($this->renderSchemaComponent($field()))->toContainElement($selector);
})->with([
    'an integer' => [static fn (): TextInput => TextInput::make('qty')->step(5), 'input[name="qty"][step="5"]'],
    'a float' => [static fn (): TextInput => TextInput::make('qty')->step(0.25), 'input[name="qty"][step="0.25"]'],
    'any' => [static fn (): TextInput => TextInput::make('qty')->step('any'), 'input[name="qty"][step="any"]'],
    'unset' => [static fn (): TextInput => TextInput::make('qty'), 'input[name="qty"]:not([step])'],
]);

it('lets an explicit step win over the one the field derives', function (): void {
    expect($this->renderSchemaComponent(TextInput::make('qty')->numeric()))->toContainElement('input[name="qty"][step="any"]')
        ->and($this->renderSchemaComponent(TextInput::make('qty')->numeric()->step(0.5)))->toContainElement('input[name="qty"][step="0.5"]')
        ->and($this->renderSchemaComponent(TextInput::make('qty')->integer()->step(10)))->toContainElement('input[name="qty"][step="10"]');
});
