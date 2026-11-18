<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\ColumnChromeTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it("lands its attributes on the header cell beside the cell's own, a later call replacing an earlier one unless merged", function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::headerCell('sku'))->matches(Selectors::HEADER_CELL . '.shelf-header[data-shelf="top"][data-bin="b2"]'))->toBeTrue()
        ->and($crawler->filter(Selectors::headerCell('note'))->matches('[data-second="yes"]:not([data-first])'))->toBeTrue();
});
