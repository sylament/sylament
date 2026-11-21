<?php

declare(strict_types=1);

use Sylament\Tables\Filters\Filter;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\ShapedJarFilterTable;
use Sylament\Tests\Fixtures\Tables\TranslatedLarderJarTable;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

it('names its field and its chip by its label', function (): void {
    $tester = $this->createLiveComponent(ShapedJarFilterTable::class)->filterTable('sealed');

    expect($tester->render()->crawler())
        ->toContainElement(Selectors::filterFieldLabel('sealed', 'isActive'), 'Sealed jars')
        ->toContainElement(Selectors::FILTER_INDICATOR_LABEL, 'Sealed jars');
});

it('derives its label from its name when given none', function (string $name, string $label): void {
    expect(Filter::make($name)->getLabel())->toBe($label);
})->with([
    'snake case' => ['is_featured', 'Is featured'],
    'camel case' => ['sealedOn', 'Sealed on'],
    'a dotted name' => ['sealed.nested', 'Sealed'],
]);

it('names its field by a label given as a message', function (): void {
    $tester = $this->createLiveComponent(TranslatedLarderJarTable::class)->filterTable('sealed');

    expect($tester->render()->crawler())->toContainElement(Selectors::filterFieldLabel('sealed', 'isActive'), 'Sealed ones (translated)');
});

it('translates a filter label under translateLabel()', function (): void {
    $translator = new Translator('en');
    $translator->addLoader('array', new ArrayLoader());
    $translator->addResource('array', ['Is featured' => 'Starred (from the default domain)', 'featured' => 'Starred too (from the default domain)'], 'en');

    $generated = Filter::make('is_featured')->translateLabel();
    $generated->setTranslator($translator);

    $written = Filter::make('is_featured')->label('featured')->translateLabel();
    $written->setTranslator($translator);

    $literal = Filter::make('is_featured')->label('featured');
    $literal->setTranslator($translator);

    expect($generated->getLabel())->toBe('Starred (from the default domain)')
        ->and($written->getLabel())->toBe('Starred too (from the default domain)')
        ->and($literal->getLabel())->toBe('featured');
});
