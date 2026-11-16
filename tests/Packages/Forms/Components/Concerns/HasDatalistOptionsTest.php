<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;

it('offers its options in a datalist its control points at, and none when it has none', function (): void {
    $render = $this->renderSchemaComponent(TextInput::make('browser')->datalist(['Firefox', 'Safari']));

    expect($render)->toContainElement('input[name="browser"][list="schema.browser-list"]')
        ->and($render->filter('datalist[id="schema.browser-list"] option')->each(static fn ($option): ?string => $option->attr('value')))->toBe(['Firefox', 'Safari'])
        ->and($this->renderSchemaComponent(TextInput::make('browser')))
        ->toContainElement('input[name="browser"]:not([list])')
        ->not->toContainElement('datalist');
});
