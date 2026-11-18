<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\ColumnChromeTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('heads a column with its label, or with its name humanised from the segment before the last dot', function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::headerCell('released-at'))->text())->toBe('Released at')
        ->and($crawler->filter(Selectors::headerCell('author.name'))->text())->toBe('Author')
        ->and($crawler->filter(Selectors::headerCell('status-code'))->text())->toBe('Status code')
        ->and($crawler->filter(Selectors::headerCell('writer'))->text())->toBe('Written by')
        ->and($crawler->filter(Selectors::headerCell('shelf') . ' em')->text())->toBe('Shelf');
});
