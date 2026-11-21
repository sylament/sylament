<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\ColumnChromeTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\TranslatedLarderJarTable;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

it('heads a column with its label, or with its name humanised from the segment before the last dot', function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::headerCell('released-at'))->text())->toBe('Released at')
        ->and($crawler->filter(Selectors::headerCell('author.name'))->text())->toBe('Author')
        ->and($crawler->filter(Selectors::headerCell('status-code'))->text())->toBe('Status code')
        ->and($crawler->filter(Selectors::headerCell('writer'))->text())->toBe('Written by')
        ->and($crawler->filter(Selectors::headerCell('shelf') . ' em')->text())->toBe('Shelf');
});

it('heads a column with a label given as a message', function (): void {
    $this->seed(new LarderJar('Plum'));

    $crawler = $this->createLiveComponent(TranslatedLarderJarTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::headerCell('label'))->text())->toBe('Name (translated)');
});

it('translates a column label under translateLabel()', function (): void {
    $translator = new Translator('en');
    $translator->addLoader('array', new ArrayLoader());
    $translator->addResource('array', ['Released at' => 'Out on (from the default domain)', 'shelf' => 'On the shelf (from the default domain)'], 'en');

    $generated = TextColumn::make('released_at')->translateLabel();
    $generated->setTranslator($translator);

    $written = TextColumn::make('released_at')->label('shelf')->translateLabel();
    $written->setTranslator($translator);

    $literal = TextColumn::make('released_at')->label('shelf');
    $literal->setTranslator($translator);

    expect($generated->getLabel())->toBe('Out on (from the default domain)')
        ->and($written->getLabel())->toBe('On the shelf (from the default domain)')
        ->and($literal->getLabel())->toBe('shelf');
});
