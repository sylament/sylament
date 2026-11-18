<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\ColumnChromeTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it("lands its attributes on each body cell beside the cell's own, a later call replacing an earlier one unless merged", function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::cell('lamp', 'sku'))->matches('.sy-ta-cell.shelf-cell[data-shelf="top"][data-bin="b2"]'))->toBeTrue()
        ->and($crawler->filter(Selectors::cell('vase', 'sku'))->matches('.sy-ta-cell.shelf-cell[data-shelf="top"][data-bin="b2"]'))->toBeTrue()
        ->and($crawler->filter(Selectors::cell('lamp', 'note'))->matches('[data-second="yes"]:not([data-first])'))->toBeTrue();
});

it('evaluates its closure for each row', function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::cell('lamp', 'name'))->attr('data-row'))->toBe('Lamp')
        ->and($crawler->filter(Selectors::cell('vase', 'name'))->attr('data-row'))->toBe('Vase');
});
