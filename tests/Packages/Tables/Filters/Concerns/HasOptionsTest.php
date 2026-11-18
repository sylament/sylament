<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\ShapedJarFilterTable;
use Symfony\Component\DomCrawler\Crawler;

it('lists its options in its select, grouped as given, and reads a grouped option in its chip', function (): void {
    $tester = $this->createLiveComponent(ShapedJarFilterTable::class);

    expect($tester->render()->crawler())->toContainElement(Selectors::filterField('kind', 'value') . ' optgroup[label="Sealed"] option[value="screw"]', 'Screw');

    $tester->filterTable('kind', 'screw');

    expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Seal kind: Screw']);
});
