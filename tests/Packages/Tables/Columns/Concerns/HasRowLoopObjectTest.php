<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\ColumnStateTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it("hands a closure its row's place in the loop in Blade's vocabulary", function (): void {
    $crawler = $this->createLiveComponent(ColumnStateTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::cell('7', 'loop'))->text())->toBe('1 of 6 index 0 5 left odd first depth 1 no parent')
        ->and($crawler->filter(Selectors::cell('jug', 'loop'))->text())->toBe('3 of 6 index 2 3 left odd depth 1 no parent')
        ->and($crawler->filter(Selectors::cell('cup', 'loop'))->text())->toBe('4 of 6 index 3 2 left even depth 1 no parent')
        ->and($crawler->filter(Selectors::cell('pot', 'loop'))->text())->toBe('6 of 6 index 5 0 left even last depth 1 no parent');
});
