<?php

declare(strict_types=1);

use Sylament\Forms\Components\Select;
use Sylament\Tests\Fixtures\Forms\Selectors;

it('renders a plain select as the native control, and native(false) as the JS control', function (): void {
    expect($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])))
        ->toContainElement(Selectors::NATIVE_SELECT)
        ->not->toContainElement(Selectors::JS_CONTROL);

    expect($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->native(false)))
        ->toContainElement(Selectors::JS_CONTROL)
        ->not->toContainElement(Selectors::NATIVE_SELECT);
});

it('renders the JS control whatever native() says once the select searches, takes several values or renders HTML labels', function (Closure $force): void {
    expect($this->renderSchemaComponent($force(Select::make('plant')->options(['fern' => 'Fern'])->native())))
        ->toContainElement(Selectors::JS_CONTROL)
        ->not->toContainElement(Selectors::NATIVE_SELECT);
})->with([
    'searchable()' => [static fn (Select $select): Select => $select->searchable()],
    'multiple()' => [static fn (Select $select): Select => $select->multiple()],
    'allowHtml()' => [static fn (Select $select): Select => $select->allowHtml()],
]);
