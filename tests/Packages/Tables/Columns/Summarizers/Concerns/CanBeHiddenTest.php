<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Columns\Summarizers\Count;
use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SummarizedJarTable;
use Sylament\Tests\Fixtures\Tables\SummarizerMountHost;
use Symfony\Component\DomCrawler\Crawler;

it('renders no figure for a hidden summarizer, and leaves a column whose every summarizer is hidden unlabelled', function (): void {
    $this->seed(new LarderJar('Apricot', new DateTimeImmutable('2024-03-05 10:00:00'), position: 1), new LarderJar('Bramble', position: 2), new LarderJar('Cherry', position: 4));

    $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['sealedOnHidden']])->render()->crawler();
    $page = $crawler->filter(Selectors::summaryRow('page'));

    expect($this::textSummaries($page))->toBe(['Sum 3', 'Count 0'])
        ->and($page->filter(Selectors::summarizer('range')))->toHaveCount(0)
        ->and($crawler->filter(Selectors::summaryRow('header'))->children()->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['Summary', 'Position', '', 'Seal', 'Larder']);
});

it('hides a summarizer on the hosts hiddenOn() names, and shows one only on the hosts visibleOn() names', function (): void {
    $column = TextColumn::make('position')->table(Table::make(new SummarizerMountHost()))->summarize([
        Sum::make('hiddenHere')->hiddenOn(SummarizerMountHost::class),
        Sum::make('hiddenElsewhere')->hiddenOn([SummarizedJarTable::class]),
        Sum::make('visibleHere')->visibleOn([SummarizedJarTable::class, SummarizerMountHost::class]),
        Sum::make('visibleElsewhere')->visibleOn(SummarizedJarTable::class),
    ]);

    expect(array_keys($column->getSummarizers($this->entityManager()->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'))))->toBe(['hiddenElsewhere', 'visibleHere']);
});

it("asks a predicate once per query a render summarizes, once each for the page, the whole set and every group", function (): void {
    $this->seed(new LarderJar('Apricot', seal: JarSeal::Wax, position: 1), new LarderJar('Bramble', seal: JarSeal::Screw, position: 2), new LarderJar('Cherry', seal: JarSeal::Wax, position: 4));

    $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['predicateHidden'], 'tableGrouping' => 'seal'])->render()->crawler();

    expect($crawler->filter(Selectors::GROUP_SUMMARY_ROW))->toHaveCount(1)
        ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe(['hidden?', 'hidden?', 'hidden?']);
});

it('asks a predicate again for a query binding an equal but distinct record, never for the same one twice', function (): void {
    $asked = 0;
    $column = TextColumn::make('position')->summarize(Count::make()->hidden(static function () use (&$asked): bool {
        $asked++;

        return false;
    }));
    $jarsOf = fn (Larder $larder): QueryBuilder => $this->entityManager()->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar')->andWhere('jar.larder = :larder')->setParameter('larder', $larder);
    $attic = new Larder('Attic');

    $column->getSummarizers($jarsOf($attic));
    $column->getSummarizers($jarsOf($attic));

    $once = $asked;
    $column->getSummarizers($jarsOf(new Larder('Attic')));

    expect($once)->toBe(1)
        ->and($asked)->toBe(2);
});
