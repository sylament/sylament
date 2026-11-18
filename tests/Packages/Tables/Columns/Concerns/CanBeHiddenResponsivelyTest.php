<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\ColumnChromeTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('hides a column from a breakpoint up, or shows it only from one, on its header, search and body cells', function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::headerCell('note'))->matches('[class~="md:sy-ta-hidden"]'))->toBeTrue()
        ->and($crawler->filter(Selectors::columnSearch('note'))->closest('td')?->matches('[class~="md:sy-ta-hidden"]'))->toBeTrue()
        ->and($crawler->filter(Selectors::cell('lamp', 'note'))->matches('[class~="md:sy-ta-hidden"]'))->toBeTrue()
        ->and($crawler->filter(Selectors::headerCell('summary'))->matches('[class~="lg:sy-ta-visible"]'))->toBeTrue()
        ->and($crawler->filter(Selectors::cell('lamp', 'summary'))->matches('[class~="lg:sy-ta-visible"]'))->toBeTrue()
        ->and($crawler->filter(Selectors::headerCell('name'))->matches('[class*=":sy-ta-"]'))->toBeFalse()
        ->and($crawler->filter(Selectors::cell('lamp', 'name'))->matches('[class*=":sy-ta-"]'))->toBeFalse();
});
