<?php

declare(strict_types=1);

use Sylament\Tables\Filters\Indicator;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\IndicatingJarFilterTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it('colours its chip as told, or as a configuration registered for every indicator says', function (): void {
    Indicator::configureUsing(static fn (Indicator $indicator): Indicator => $indicator->color('warning'));

    expect($this->createLiveComponent(IndicatingJarFilterTable::class)->render()->crawler())
        ->toContainElement(Selectors::FILTER_INDICATOR . SupportSelectors::color('warning') . ' ' . SupportSelectors::BADGE_LABEL, 'Worded chip')
        ->toContainElement(Selectors::FILTER_INDICATOR . SupportSelectors::color('success') . ' ' . SupportSelectors::BADGE_LABEL, 'Built chip');
});

it('shows no remove control on a chip that is not removable, whose filter outlasts removing every filter', function (): void {
    $this->seed($plum = new LarderJar('Plum', seal: JarSeal::Screw), $open = new LarderJar('Open'));

    $tester = $this->createLiveComponent(IndicatingJarFilterTable::class)
        ->filterTable('pinned')
        ->filterTable('fielded');

    $removeControls = static fn (Crawler $crawler, string $label): int => $crawler->filter(Selectors::FILTER_INDICATOR)
        ->reduce(static fn (Crawler $chip): bool => $chip->filter(SupportSelectors::BADGE_LABEL)->text() === $label)
        ->filter(Selectors::REMOVE_FILTER_BUTTON)
        ->count();

    expect($removeControls($tester->render()->crawler(), 'Pinned chip'))->toBe(0)
        ->and($removeControls($tester->render()->crawler(), 'Field chip'))->toBe(1);

    $tester->removeTableFilters()
        ->assertCanSeeTableRecords([$plum])
        ->assertCanNotSeeTableRecords([$open]);

    expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))
        ->toContain('Pinned chip')
        ->not->toContain('Field chip');
});

it("names the field it removes on its chip's remove control", function (): void {
    $tester = $this->createLiveComponent(IndicatingJarFilterTable::class)->filterTable('fielded');

    expect($tester->render()->crawler())->toContainElement(Selectors::removeFilterButton('fielded', 'isActive'));

    $tester->removeTableFilter('fielded', 'isActive');

    expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->not->toContain('Field chip');
});
