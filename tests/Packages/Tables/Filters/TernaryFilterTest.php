<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\ColumnKindSample;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Tables\DefaultedJarFilterTable;
use Sylament\Tests\Fixtures\Tables\FilterableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\FlaggedSampleFilterTable;
use Sylament\Tests\Fixtures\Tables\QueriedTernaryJarTable;
use Sylament\Tests\Fixtures\Tables\QueryingJarFilterTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it('offers a dash, then Yes as 1 and No as 0', function (): void {
    $crawler = $this->createLiveComponent(FlaggedSampleFilterTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::filterField('flagged', 'value') . ' option')->each(static fn (Crawler $option): array => [$option->attr('value'), $option->text()]))
        ->toBe([['', '-'], ['1', 'Yes'], ['0', 'No']]);
});

it('keeps the rows its attribute is true for on Yes, false for on No, and every row while blank', function (mixed $value, array $kept): void {
    $this->seed(new ColumnKindSample('Flagged', flagged: true), new ColumnKindSample('Plain'));

    $tester = $this->createLiveComponent(FlaggedSampleFilterTable::class)->filterTable('flagged', $value);

    expect($tester->render()->crawler()->filter(Selectors::ROW . ' ' . Selectors::TEXT_COLUMN)->each(static fn (Crawler $cell): string => $cell->text()))->toEqualCanonicalizing($kept);
})->with([
    'Yes' => [1, ['Flagged']],
    'No' => [0, ['Plain']],
    'blank' => [null, ['Flagged', 'Plain']],
]);

it('keeps the rows its nullable attribute holds a value for on Yes, none for on No, and every row while blank', function (mixed $value, array $kept): void {
    $this->seed(new LarderJar('Plum', sealedOn: new DateTimeImmutable('2026-01-01')), new LarderJar('Open'));

    $tester = $this->createLiveComponent(FilterableLarderJarTable::class)->filterTable('sealedOn', $value);

    expect($tester->render()->crawler()->filter(Selectors::ROW . ' ' . Selectors::TEXT_COLUMN)->each(static fn (Crawler $cell): string => $cell->text()))->toEqualCanonicalizing($kept);
})->with([
    'Yes' => [1, ['Plum']],
    'No' => [0, ['Open']],
    'blank' => [null, ['Plum', 'Open']],
]);

it('reads a bare attribute on the root of a joined query, and a dotted one on its alias', function (): void {
    $farm = new Product('Farm');
    $suppliedLarder = new Larder('Pantry');
    $suppliedLarder->supplier = $farm;
    $suppliedLarder->addJar($inSuppliedLarder = new LarderJar('Fig', position: 1));

    $bareLarder = new Larder('Cellar');
    $bareLarder->addJar($supplied = new LarderJar('Plum', supplier: $farm, position: 1));
    $this->seed($farm, $suppliedLarder, $bareLarder);

    $this->createLiveComponent(QueryingJarFilterTable::class)
        ->filterTable('supplier', 1)
        ->assertCanSeeTableRecords([$supplied])
        ->assertCanNotSeeTableRecords([$inSuppliedLarder]);

    $this->createLiveComponent(QueryingJarFilterTable::class)
        ->filterTable('larderSupplied', 1)
        ->assertCanSeeTableRecords([$inSuppliedLarder])
        ->assertCanNotSeeTableRecords([$supplied]);
});

it('takes its true, false or blank arm by its state', function (mixed $value, array $kept): void {
    $this->seed(new LarderJar('Plum', seal: JarSeal::Wax), new LarderJar('Fig', seal: JarSeal::Screw, position: 1), new LarderJar('Open', position: 2));

    $tester = $this->createLiveComponent(QueriedTernaryJarTable::class)->filterTable('waxed', $value);

    expect($tester->render()->crawler()->filter(Selectors::ROW . ' ' . Selectors::TEXT_COLUMN)->each(static fn (Crawler $cell): string => $cell->text()))->toEqualCanonicalizing($kept);
})->with([
    'Yes' => [1, ['Plum']],
    'No' => [0, ['Fig']],
    'blank' => [null, ['Fig', 'Open']],
]);

it('runs a query given in place of its arms whichever way it is set, and shows a built indicator as it is', function (): void {
    $this->seed(new LarderJar('Plum', seal: JarSeal::Wax, position: 1), $fig = new LarderJar('Fig', seal: JarSeal::Screw, position: 1));

    $tester = $this->createLiveComponent(QueriedTernaryJarTable::class)
        ->filterTable('figOnly', 0)
        ->assertCanSeeTableRecords([$fig])
        ->assertCountTableRecords(1)
        ->filterTable('figOnly', 1)
        ->assertCanSeeTableRecords([$fig])
        ->assertCountTableRecords(1);

    expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Fig chip']);
});

it('starts on No and filters by it when its default is false', function (): void {
    $this->seed($open = new LarderJar('Open', seal: JarSeal::Wax), $plum = new LarderJar('Plum', sealedOn: new DateTimeImmutable('2026-01-01'), seal: JarSeal::Wax));

    $tester = $this->createLiveComponent(DefaultedJarFilterTable::class)
        ->assertCanSeeTableRecords([$open])
        ->assertCanNotSeeTableRecords([$plum]);

    expect($tester->render()->crawler())->toContainElement(Selectors::filterField('sealedOn', 'value') . ' option[value="0"][selected]', 'No');
});

it('labels its options and its chip with the labels it is given, over any options it was given', function (): void {
    $tester = $this->createLiveComponent(QueryingJarFilterTable::class)->filterTable('larderSupplied', 0)->filterTable('supplier', 1);

    $crawler = $tester->render()->crawler();

    expect($crawler->filter(Selectors::filterField('larderSupplied', 'value') . ' option')->each(static fn (Crawler $option): string => $option->text()))->toBe(['-', 'Supplied', 'Unsupplied'])
        ->and($crawler->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Supplier: Yes', 'Larder supplied: Unsupplied']);

    $tester->filterTable('larderSupplied', null);

    expect($tester->render()->crawler()->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Supplier: Yes']);
});
