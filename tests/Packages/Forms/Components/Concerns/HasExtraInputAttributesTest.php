<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Forms\Selectors;

describe('extraInputAttributes()', function (): void {
    it('puts its attributes on the control, a second call replacing them unless told to merge, a closure answering nothing adding none', function (): void {
        expect($this->renderSchemaComponent(TextInput::make('code')->extraInputAttributes(['data-first' => 'a'])->extraInputAttributes(['data-second' => 'b'])))
            ->toContainElement('input[name="code"][data-second="b"]:not([data-first])')
            ->and($this->renderSchemaComponent(TextInput::make('code')
                ->extraInputAttributes(['data-first' => 'a'])
                ->extraInputAttributes(['data-second' => 'b'], merge: true)
                ->extraInputAttributes(static fn (): null => null, merge: true)))
            ->toContainElement('input[name="code"][data-first="a"][data-second="b"]');
    });

    it('lets its attributes win over those the field computes, its classes appending, while the controller keeps its target', function (): void {
        expect($this->renderSchemaComponent(TextInput::make('amount')->numeric()->disabled()->extraInputAttributes(['inputmode' => 'email', 'disabled' => false, 'class' => 'wide'])))
            ->toContainElement('input.sy-fo-input.wide[name="amount"][inputmode="email"]:not([disabled])')
            ->and($this->renderSchemaComponent(TextInput::make('code')->copyable()->extraInputAttributes(['data-sylament--forms--text-input-target' => 'other'])))
            ->toContainElement('input[name="code"]' . Selectors::TEXT_INPUT_TARGET);
    });
});
