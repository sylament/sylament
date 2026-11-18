<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\ArrangedJarFilterTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it('chips the global search first, then each column search, then every active filter in the order the table declares them, each removed by its own live action', function (): void {
    $crawler = $this->createLiveComponent(ArrangedJarFilterTable::class)
        ->searchTable('Plum')
        ->searchTableColumns(['label' => 'Pl'])
        ->filterTable('seal', JarSeal::Wax)
        ->filterTable('labelled')
        ->render()
        ->crawler();

    expect($crawler->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Search: Plum', 'Label: Pl', 'Labelled', 'Seal: Wax'])
        ->and($crawler->filter(Selectors::FILTER_INDICATOR . ' ' . Selectors::REMOVE_FILTER_BUTTON)->each(static fn (Crawler $control): array => [
            $control->attr('data-live-action-param'),
            $control->attr('data-live-column-param') ?? $control->attr('data-live-filter-name-param'),
        ]))->toBe([
            ['resetTableSearch', null],
            ['resetTableColumnSearch', 'label'],
            ['removeTableFilter', 'labelled'],
            ['removeTableFilter', 'seal'],
        ]);
});

it('shows no chip while the table hides its indicators, though its filters still narrow the rows and count on the trigger', function (): void {
    $this->seed($plum = new LarderJar('Plum'), $unlabelled = new LarderJar());

    $tester = $this->createLiveComponent(ArrangedJarFilterTable::class, ['variants' => ['indicatorsHidden']])
        ->filterTable('labelled')
        ->assertCanSeeTableRecords([$plum])
        ->assertCanNotSeeTableRecords([$unlabelled]);
    $crawler = $tester->render()->crawler();

    expect($crawler)->toContainElement(Selectors::FILTERS_TRIGGER_BADGE, '1')
        ->and($crawler->filter(Selectors::FILTER_INDICATOR))->toHaveCount(0);
});
