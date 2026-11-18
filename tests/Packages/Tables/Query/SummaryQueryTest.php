<?php

declare(strict_types=1);

use Sylament\Schemas\Query\SortDirections;
use Sylament\Tables\Columns\Summarizers\Count;
use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\ShelvedPreserve;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SummarizedLarderTable;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Error\RuntimeError;

it('reads its figure over the whole set whatever the order and the window of the query, which it leaves as it was', function (): void {
    $this->seed(new LarderJar(position: 1), new LarderJar(position: 2), new LarderJar(position: 4));

    $query = $this->entityManager()->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar')->orderBy('jar.position', SortDirections::from('desc'))->setFirstResult(1)->setMaxResults(1);
    $crawler = $this->renderSummarizer(Sum::make(), query: $query);

    expect($crawler)->toContainElement(Selectors::summarizerFigure('text'), '7')
        ->and($query->getDQL())->toContain('ORDER BY jar.position DESC')
        ->and([$query->getFirstResult(), $query->getMaxResults()])->toBe([1, 1]);
});

it("reads a to-one path off a join and an embeddable's field off the root", function (): void {
    $attic = new Larder('Attic');
    $attic->addJar(new LarderJar('Apricot'));
    $this->seed($attic, new LarderJar('Bramble'), new ShelvedPreserve(position: 2), new ShelvedPreserve(position: 3));

    $preserves = $this->entityManager()->createQueryBuilder()->select('preserve')->from(ShelvedPreserve::class, 'preserve');

    expect($this->renderSummarizer(Count::make(), 'larder.name'))->toContainElement(Selectors::summarizerFigure('text'), '1')
        ->and($this->renderSummarizer(Sum::make(), 'spot.position', $preserves))->toContainElement(Selectors::summarizerFigure('text'), '5');
});

it('refuses to aggregate through a to-many path, naming the way out', function (): void {
    $larders = $this->entityManager()->createQueryBuilder()->select('larder')->from(Larder::class, 'larder');

    expect(fn (): Crawler => $this->renderSummarizer(Sum::make(), 'jars.position', $larders))
        ->toThrow(LogicException::class, 'Cannot summarize [jars.position]: summarizing through a relation supports to-one associations only. Declare an aggregate column, or take over with Summarizer::using().');
});

it('refuses to summarize a table whose query joins a to-many association, naming the way out', function (): void {
    $this->seed(new Larder('Attic'));

    expect(fn (): mixed => $this->createLiveComponent(SummarizedLarderTable::class, ['variants' => ['joined']])->render())
        ->toThrow(RuntimeError::class, 'Cannot summarize this table: its query joins a to-many association, which multiplies rows and inflates every aggregate over them. Drop the join, or take the summary over with Summarizer::using().');
});
