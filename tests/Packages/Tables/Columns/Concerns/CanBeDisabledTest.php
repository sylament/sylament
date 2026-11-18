<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\ColumnChromeTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it("renders a disabled column's control disabled", function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::cell('lamp', 'active') . ' input[type="checkbox"][disabled]'))->toHaveCount(1)
        ->and($crawler->filter(Selectors::cell('lamp', 'stocked') . ' input[type="checkbox"]:not([disabled])'))->toHaveCount(1);
});

it('takes the link off a cell whose click is disabled', function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::cell('lamp', 'link') . ' ' . Selectors::CELL_WRAPPER)->nodeName())->toBe('a')
        ->and($crawler->filter(Selectors::cell('lamp', 'unclickable') . ' ' . Selectors::CELL_WRAPPER)->nodeName())->toBe('div')
        ->and($crawler->filter(Selectors::cell('lamp', 'unclickable') . ' a'))->toHaveCount(0);
});
