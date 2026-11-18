<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\ColumnChromeTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('shows a toggleable column unless it starts toggled off, and never offers a hidden one to the column manager', function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::headerCell('toggleable')))->toHaveCount(1)
        ->and($crawler->filter(Selectors::headerCell('toggled-off')))->toHaveCount(0)
        ->and($crawler->filter(Selectors::COLUMN_MANAGER . ' input[value="toggleable"][checked]:not([disabled])'))->toHaveCount(1)
        ->and($crawler->filter(Selectors::COLUMN_MANAGER . ' input[value="toggledOff"]:not([checked]):not([disabled])'))->toHaveCount(1)
        ->and($crawler->filter(Selectors::COLUMN_MANAGER . ' input[value="name"][disabled]'))->toHaveCount(1)
        ->and($crawler->filter(Selectors::COLUMN_MANAGER . ' input[value="hiddenToggleable"]'))->toHaveCount(0);
});
