<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\IndicatingJarFilterTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\ShapedJarFilterTable;
use Symfony\Component\DomCrawler\Crawler;

it('shows the chips its indicator callback returns, as a string, a built indicator or a list', function (): void {
    $crawler = $this->createLiveComponent(IndicatingJarFilterTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Worded chip', 'Built chip', 'First chip', 'Second chip']);
});

it('labels its chip with its indicator in place of its label', function (): void {
    $tester = $this->createLiveComponent(IndicatingJarFilterTable::class)->filterTable('waxed');

    expect($tester->render()->crawler())->toContainElement(Selectors::FILTER_INDICATOR_LABEL, 'Wax only')
        ->and($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->not->toContain('Waxed');
});

it('removes only the field a chip is keyed by', function (): void {
    $tester = $this->createLiveComponent(ShapedJarFilterTable::class)->filterTable('stocked', ['state' => 'shut', 'note' => 'Top shelf']);

    expect($tester->render()->crawler())->toContainElement(Selectors::REMOVE_FILTER_BUTTON . '[data-live-filter-name-param="stocked"][data-live-field-param="note"]');

    $tester->removeTableFilter('stocked', 'note');

    expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['State: shut']);
});
