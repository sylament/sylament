<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Actions\Selectors;

it('submits its enclosing form, the one whose id it is given from outside it, and is a plain button otherwise', function (Closure $look, string $selector): void {
    $trigger = Selectors::TRIGGER . $selector;

    expect($this->renderViewComponent($look(Action::make('save')->submit('save'))))
        ->toContainElement("button{$trigger}[type=\"submit\"]:not([form])")
        ->and($this->renderViewComponent($look(Action::make('save')->submit('save')->formId('order-form'))))
        ->toContainElement("button{$trigger}[type=\"submit\"][form=\"order-form\"]")
        ->and($this->renderViewComponent($look(Action::make('save'))))
        ->toContainElement("button{$trigger}[type=\"button\"]:not([form])")
        ->and($this->renderViewComponent($look(Action::make('save')->submit('order-form')->submit(null))))
        ->toContainElement("button{$trigger}[type=\"button\"]");
})->with('trigger looks');
