<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\ColumnStateTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it("keys each cell by its record's key, reading an integer key as its string form", function (): void {
    $crawler = $this->createLiveComponent(ColumnStateTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::cell('7', 'name'))->text())->toBe('Lamp');
});
