<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Columns\Summarizers\Range;
use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tables\Columns\Summarizers\Summarizer;
use Sylament\Tables\Columns\Summarizers\Values;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SummarizedJarTable;
use Sylament\Tests\Fixtures\Tables\SummarizedLarderTable;
use Twig\Error\RuntimeError;

describe('using()', function (): void {
    it("answers with the figure a using() closure computes in place of its own, handed the column's name and the query it summarizes", function (): void {
        $this->seed(new LarderJar(position: 2), new LarderJar(position: 5));

        $crawler = $this->renderSummarizer(Sum::make()->using(static fn (string $attribute, QueryBuilder $query): string => "{$attribute} up to " . (clone $query)->select('MAX(jar.position)')->getQuery()->getSingleScalarResult()));

        expect($crawler)->toContainElement(Selectors::summarizerFigure('text'), 'position up to 5');
    });

    it('lets a using() closure answer for a relationship aggregate and for a to-many path, the rest of the band batching beside them', function (): void {
        $larder = new Larder('Attic');
        $larder->addJar(new LarderJar('Apricot'));
        $this->seed($larder, new Larder('Cellar'));

        $all = $this->createLiveComponent(SummarizedLarderTable::class)->render()->crawler()->filter(Selectors::summaryRow('all'));

        expect($all)->toContainElement(Selectors::summarizerFigure('text'), 'jars_count counted')
            ->and($all)->toContainElement(Selectors::SUMMARIZER_VALUE, 'jars.label')
            ->and($all)->toContainElement(Selectors::summarizerFigure('text'), '2');
    });

    it('refuses to summarize a relationship aggregate without one, naming the column and the way out', function (): void {
        $this->seed(new Larder('Attic'));

        expect(fn (): mixed => $this->createLiveComponent(SummarizedLarderTable::class, ['variants' => ['aggregateSummed']])->render())
            ->toThrow(RuntimeError::class, 'Cannot summarize [jars_count]: the column reads a relationship aggregate, which is filled in per record from a side store and which DQL cannot aggregate again. Take the summary over with Summarizer::using().');
    });
});

it('reads a batched figure from the batch with no statement of its own, a sum over nothing included', function (array $jars, ?string $search, string $sum, int $sums): void {
    $this->seed(...$jars);

    $tester = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['columnSearch']]);

    if ($search !== null) {
        $tester->searchTableColumns(['label' => $search]);
    }

    $all = $tester->render()->crawler()->filter(Selectors::summaryRow('all'));

    expect($all->filter(Selectors::summarizerFigure('text'))->first()->text())->toBe($sum)
        ->and(array_values(preg_grep('/\bSUM\(/', $this->statementsRun())))->toHaveCount($sums)
        ->each->toContain('COUNT(');
})->with([
    'a page and the whole set' => [fn (): array => [new LarderJar('Apricot', position: 1), new LarderJar('Bramble', position: 2), new LarderJar('Cherry', position: 4)], null, '7', 2],
    'nothing left to sum' => [fn (): array => [new LarderJar('Apricot', position: 1)], 'Quince', 'No sum', 1],
]);

it('refuses to summarize a records() table, naming the column and the ways out', function (): void {
    expect(fn (): mixed => $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['records', 'positionOnly']])->render())
        ->toThrow(RuntimeError::class, 'Cannot summarize [position]: summaries read their figures from the table query, and this table lists records() rows the toolkit never queried. Use query() or relationship(), or drop the summarizer.');
});

it('formats its figure once for the one body that prints it', function (): void {
    $this->seed(new LarderJar(position: 1), new LarderJar(position: 2));
    $runs = 0;

    $crawler = $this->renderSummarizer(Sum::make()->formatStateUsing(static function (int $state) use (&$runs): string {
        $runs++;

        return "{$state} jars";
    }));

    expect($crawler)->toContainElement(Selectors::summarizerFigure('text'), '3 jars')
        ->and($runs)->toBe(1);
});

it("puts its extra attributes on its body's root, whichever body it renders", function (Closure $summarizer, string $body, string $column): void {
    $this->seed(new LarderJar(seal: JarSeal::Wax, position: 1));

    $root = $this->renderSummarizer($summarizer()->extraAttributes(['data-shelf' => 'top']), $column)->filter('[data-shelf="top"]');

    expect($root)->toHaveCount(1)
        ->and($root->matches(Selectors::summarizer($body)))->toBeTrue();
})->with([
    'a text body' => [fn (): Summarizer => Sum::make(), 'text', 'position'],
    'a range body' => [fn (): Summarizer => Range::make(), 'range', 'position'],
    'a values body' => [fn (): Summarizer => Values::make(), 'values', 'seal'],
]);
