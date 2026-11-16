<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Forms\Selectors;

describe('extraFieldWrapperAttributes()', function (): void {
    it('puts its attributes on the field wrapper, a second call replacing them unless told to merge', function (): void {
        expect($this->renderSchemaComponent(TextInput::make('code')->extraFieldWrapperAttributes(['data-first' => 'a'])->extraFieldWrapperAttributes(['data-second' => 'b'])))
            ->toContainElement(Selectors::FIELD_WRAPPER . '[data-second="b"]:not([data-first])')
            ->and($this->renderSchemaComponent(TextInput::make('code')
                ->extraFieldWrapperAttributes(['data-first' => 'a', 'class' => 'one'])
                ->extraFieldWrapperAttributes(['data-second' => 'b', 'class' => 'two'], merge: true)))
            ->toContainElement(Selectors::FIELD_WRAPPER . '.sy-fo-field.one.two[data-first="a"][data-second="b"]');
    });

    it('lets extraAttributes() win over it on the wrapper, both classes appending to its own', function (): void {
        expect($this->renderSchemaComponent(TextInput::make('code')
            ->extraFieldWrapperAttributes(['data-tone' => 'wrapper', 'class' => 'from-wrapper', 'style' => 'color: red'])
            ->extraAttributes(['data-tone' => 'field', 'class' => 'from-field'])))
            ->toContainElement(Selectors::FIELD_WRAPPER . '.sy-fo-field.from-wrapper.from-field[data-tone="field"][style="color: red"]');
    });
});
