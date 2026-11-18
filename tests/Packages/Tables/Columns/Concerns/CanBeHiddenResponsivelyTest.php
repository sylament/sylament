<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\ColumnChromeTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('hides a column from a breakpoint up, or shows it only from one, on its header, search and body cells', function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::headerCell('note'))->matches(Selectors::hiddenFrom('md')))->toBeTrue()
        ->and($crawler->filter(Selectors::columnSearch('note'))->closest('td')?->matches(Selectors::hiddenFrom('md')))->toBeTrue()
        ->and($crawler->filter(Selectors::cell('lamp', 'note'))->matches(Selectors::hiddenFrom('md')))->toBeTrue()
        ->and($crawler->filter(Selectors::headerCell('summary'))->matches(Selectors::visibleFrom('lg')))->toBeTrue()
        ->and($crawler->filter(Selectors::cell('lamp', 'summary'))->matches(Selectors::visibleFrom('lg')))->toBeTrue()
        ->and($crawler->filter(Selectors::headerCell('name')))->toHaveCount(1)
        ->and($crawler->filter(Selectors::headerCell('name'))->matches(Selectors::RESPONSIVE))->toBeFalse()
        ->and($crawler->filter(Selectors::cell('lamp', 'name')))->toHaveCount(1)
        ->and($crawler->filter(Selectors::cell('lamp', 'name'))->matches(Selectors::RESPONSIVE))->toBeFalse();
});
