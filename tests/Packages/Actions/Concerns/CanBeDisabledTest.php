<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('renders a disabled trigger when disabled or hidden', function (Closure $action): void {
    expect($this->renderViewComponent($action()))->toContainElement('button[disabled][aria-disabled="true"]' . SupportSelectors::DISABLED, 'Save')
        ->and($this->renderViewComponent(Action::make('save')))->toContainElement('button:not([disabled])', 'Save');
})->with([
    'disabled' => [fn (): Action => Action::make('save')->disabled()],
    'hidden' => [fn (): Action => Action::make('save')->hidden()],
]);
