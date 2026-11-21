<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Fieldset;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

use function Symfony\Component\Translation\t;

it('names its fieldset by its label', function (): void {
    expect($this->renderSchemaComponent(Fieldset::make()->label('Shipping')->components([Text::make('a')])))
        ->toContainElement(Selectors::FIELDSET . ' legend', 'Shipping');
});

it('keeps a hidden label in the markup, hidden only from view', function (): void {
    expect($this->renderSchemaComponent(Fieldset::make('Shipping')->hiddenLabel()->components([Text::make('a')])))
        ->toContainElement(Selectors::FIELDSET . SupportSelectors::FIELDSET_LABEL_HIDDEN . ' legend', 'Shipping');
});

it('translates a label given as a message', function (): void {
    expect($this->renderSchemaComponent(Fieldset::make(t('account.label', [], 'fixtures-schemas'))->components([Text::make('a')])))
        ->toContainElement(Selectors::FIELDSET . ' legend', 'Account (translated)');
});

it('translates a string label as a key in the default domain under translateLabel(), and shows it as written without', function (): void {
    $translator = new Translator('en');
    $translator->addLoader('array', new ArrayLoader());
    $translator->addResource('array', ['account' => 'Account (from the default domain)'], 'en');

    $translated = Fieldset::make('account')->translateLabel()->components([Text::make('a')]);
    $translated->setTranslator($translator);

    $literal = Fieldset::make('account')->components([Text::make('a')]);
    $literal->setTranslator($translator);

    expect($this->renderSchemaComponent($translated))->toContainElement(Selectors::FIELDSET . ' legend', 'Account (from the default domain)')
        ->and($this->renderSchemaComponent($literal))->toContainElement(Selectors::FIELDSET . ' legend', 'account');
});
