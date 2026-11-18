<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\QueryingJarFilterTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\ShapedJarFilterTable;
use Symfony\Component\DomCrawler\Crawler;

it('renders a checkbox named by its label, a toggle in its place when told, and the checkbox again when told back', function (): void {
    $crawler = $this->createLiveComponent(ShapedJarFilterTable::class)->render()->crawler();

    expect($crawler)
        ->toContainElement('label[for="tableFiltersForm.sealed.isActive"]', 'Sealed jars')
        ->toContainElement(Selectors::filterField('sealed', 'isActive') . '[type="checkbox"]:not([role="switch"])')
        ->toContainElement('label[for="tableFiltersForm.waxed.isActive"]', 'Waxed')
        ->toContainElement(Selectors::filterField('waxed', 'isActive') . '[type="checkbox"][role="switch"]')
        ->toContainElement(Selectors::filterField('screwed', 'isActive') . '[type="checkbox"]:not([role="switch"])');
});

it('shows one chip labelled as the filter while it is ticked, and none once it is unticked', function (): void {
    $this->seed(new LarderJar('Plum', seal: JarSeal::Wax), $open = new LarderJar('Open'));

    $tester = $this->createLiveComponent(ShapedJarFilterTable::class)->filterTable('sealed');

    expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Sealed jars']);

    $tester->filterTable('sealed', false)->assertCanSeeTableRecords([$open]);

    expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->count())->toBe(0);
});

it('shows no chip for a filter whose fields hold no isActive', function (): void {
    $tester = $this->createLiveComponent(QueryingJarFilterTable::class)->filterTable('labelled');

    expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Labelled']);
});

it('unticks the box and drops its chip when it is removed or the form reset, whatever reset state it was given', function (): void {
    $this->seed($plum = new LarderJar('Plum', seal: JarSeal::Wax), $unlabelled = new LarderJar(seal: JarSeal::Wax), $open = new LarderJar('Open'));

    $tester = $this->createLiveComponent(ShapedJarFilterTable::class)
        ->filterTable('sealed')
        ->filterTable('kept')
        ->assertCanSeeTableRecords([$plum])
        ->assertCanNotSeeTableRecords([$unlabelled, $open])
        ->removeTableFilter('kept')
        ->assertCanSeeTableRecords([$plum, $unlabelled])
        ->assertCanNotSeeTableRecords([$open]);

    expect($tester->render()->crawler())
        ->toContainElement(Selectors::filterField('sealed', 'isActive') . '[checked]')
        ->toContainElement(Selectors::filterField('kept', 'isActive') . ':not([checked])')
        ->and($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Sealed jars']);

    $tester->resetTableFilters()->assertCanSeeTableRecords([$plum, $unlabelled, $open]);

    expect($tester->render()->crawler())
        ->toContainElement(Selectors::filterField('sealed', 'isActive') . ':not([checked])')
        ->and($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->count())->toBe(0);
});
