<?php

declare(strict_types=1);

use Sylament\Forms\Components\Textarea;

it('tells Grammarly to leave its control alone when told, and not when the condition is false or it is not told', function (Closure $field, string $selector): void {
    expect($this->renderSchemaComponent($field()))->toContainElement($selector);
})->with([
    'disabled' => [static fn (): Textarea => Textarea::make('notes')->disableGrammarly(), 'textarea[name="notes"][data-gramm="false"][data-gramm_editor="false"][data-enable-grammarly="false"]'],
    'a false condition' => [static fn (): Textarea => Textarea::make('notes')->disableGrammarly(false), 'textarea[name="notes"]:not([data-gramm]):not([data-gramm_editor]):not([data-enable-grammarly])'],
    'unset' => [static fn (): Textarea => Textarea::make('notes'), 'textarea[name="notes"]:not([data-gramm]):not([data-gramm_editor]):not([data-enable-grammarly])'],
]);
