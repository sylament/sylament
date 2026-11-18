<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\IndicatingJarFilterTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it("removes a chip's filter through the live action its control sends, naming no field it was not given", function (): void {
    $tester = $this->createLiveComponent(IndicatingJarFilterTable::class)->filterTable('waxed');

    $crawler = $tester->render()->crawler();

    expect($crawler)
        ->toContainElement(Selectors::removeFilterButton('waxed') . Selectors::REMOVE_FILTER_ACTION)
        ->toContainElement(Selectors::removeFilterButton('listed'))
        ->and($crawler->filter(Selectors::removeFilterButton('listed') . '[data-live-field-param]')->count())->toBe(0);

    $tester->removeTableFilter('waxed');

    expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->not->toContain('Wax only');
});
