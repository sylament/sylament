<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

use function Symfony\Component\Translation\t;

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

it('translates a label given as a message', function (): void {
    expect($this->renderViewComponent(Action::make('archive')->label(t('trigger.label', [], 'fixtures-actions'))))
        ->toContainElement(Selectors::TRIGGER, 'Archive (translated)');
});

it('translates a string label as a key in the default domain under translateLabel(), generated label included, and shows it as written without', function (): void {
    $translator = new Translator('en');
    $translator->addLoader('array', new ArrayLoader());
    $translator->addResource('array', ['archive' => 'Archive (from the default domain)', 'Send invoice' => 'Invoice it (from the default domain)'], 'en');

    $translated = Action::make('archive')->label('archive')->translateLabel();
    $translated->setTranslator($translator);

    $generated = Action::make('sendInvoice')->translateLabel();
    $generated->setTranslator($translator);

    $literal = Action::make('archive')->label('archive');
    $literal->setTranslator($translator);

    expect($this->renderViewComponent($translated))->toContainElement(Selectors::TRIGGER, 'Archive (from the default domain)')
        ->and($this->renderViewComponent($generated))->toContainElement(Selectors::TRIGGER, 'Invoice it (from the default domain)')
        ->and($this->renderViewComponent($literal))->toContainElement(Selectors::TRIGGER, 'archive');
});
