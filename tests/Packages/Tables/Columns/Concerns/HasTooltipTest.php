<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\ColumnChromeTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\TranslatedLarderJarTable;

it("titles a cell's items, its header cell and its empty cell with its tooltips, as plain text", function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::cell('lamp', 'name') . ' [title]')->attr('title'))->toBe('About Lamp')
        ->and($crawler->filter(Selectors::cell('vase', 'name') . ' [title]')->attr('title'))->toBe('About Vase')
        ->and($crawler->filter(Selectors::headerCell('name'))->attr('title'))->toBe('Header tip')
        ->and($crawler->filter(Selectors::cell('lamp', 'missing') . ' ' . Selectors::TEXT_COLUMN)->attr('title'))->toBe('Nothing here')
        ->and($crawler->filter(Selectors::headerCell('released-at'))->attr('title'))->toBeNull()
        ->and($crawler->filter(Selectors::cell('lamp', 'releasedAt') . ' [title]'))->toHaveCount(0);
});

it('titles its header cell and its cell with tooltips given as messages in their translation', function (): void {
    $this->seed($plum = new LarderJar('Plum'));

    $crawler = $this->createLiveComponent(TranslatedLarderJarTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::headerCell('label'))->attr('title'))->toBe('Jar names (translated)')
        ->and($crawler->filter(Selectors::cell((string) $plum->id, 'label') . ' [title]')->attr('title'))->toBe('The name (translated)');
});
