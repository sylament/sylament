<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\ShapedJarFilterTable;
use Symfony\Component\DomCrawler\Crawler;

it('writes its reset state back to the fields it names when its chip is removed, and clears the others', function (): void {
    $tester = $this->createLiveComponent(ShapedJarFilterTable::class)->filterTable('stocked', ['state' => 'shut', 'note' => 'Top shelf']);

    expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['State: shut', 'Note: Top shelf']);

    $tester->removeTableFilter('stocked');

    expect($tester->render()->crawler())->toContainElement(Selectors::filterField('stocked', 'state') . ' option[value="open"][selected]')
        ->and($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['State: open']);
});
