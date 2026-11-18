<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\ColumnChromeTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('leaves out a column hidden outright, made invisible, hidden on its host or visible only on another, hidden winning over visible', function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::headerCell('visible-here')))->toHaveCount(1)
        ->and($crawler->filter(Selectors::cell('lamp', 'visibleHere')))->toHaveCount(1)
        ->and($crawler->filter(Selectors::headerCell('hidden')))->toHaveCount(0)
        ->and($crawler->filter(Selectors::cell('lamp', 'hidden')))->toHaveCount(0)
        ->and($crawler->filter(Selectors::headerCell('invisible')))->toHaveCount(0)
        ->and($crawler->filter(Selectors::headerCell('hidden-here')))->toHaveCount(0)
        ->and($crawler->filter(Selectors::headerCell('visible-elsewhere')))->toHaveCount(0)
        ->and($crawler->filter(Selectors::headerCell('overruled')))->toHaveCount(0);
});
