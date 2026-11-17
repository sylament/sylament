<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it("labels its trigger with its label, or with its name's segment before the first dot, in words with a capital first", function (Closure $action, string $label): void {
    expect($this->renderViewComponent($action()))->toContainElement(Selectors::TRIGGER, $label);
})->with([
    'a label' => [fn (): Action => Action::make('save')->label('Keep it'), 'Keep it'],
    'a camel-cased name' => [fn (): Action => Action::make('sendInvoice'), 'Send invoice'],
    'a dotted name' => [fn (): Action => Action::make('save.confirm'), 'Save'],
]);

it('hides its label from view and keeps it as the accessible name', function (Closure $action, string $look): void {
    $render = $this->renderViewComponent($action()->hiddenLabel());

    expect($render)->toContainButtons($look, ['Send'])
        ->and($render->filter($look)->text())->toBe('')
        ->and($this->renderViewComponent($action()))->toContainElement($look, 'Send');
})->with([
    'a badge' => [fn (): Action => Action::make('send')->badge(), SupportSelectors::BADGE],
    'a button' => [fn (): Action => Action::make('send'), SupportSelectors::BUTTON],
    'a link' => [fn (): Action => Action::make('send')->link(), SupportSelectors::LINK],
]);
