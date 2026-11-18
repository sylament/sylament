<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('scopes its figure through a query() closure, by the builder it returns or the one it was handed, until null clears the scope', function (Closure $summarizer, string $total): void {
    $this->seed(new LarderJar(position: 1), new LarderJar(position: 2), new LarderJar(position: 4));

    expect($this->renderSummarizer($summarizer()))->toContainElement(Selectors::summarizerFigure('text'), $total);
})->with([
    'a returned builder' => [fn (): Sum => Sum::make()->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere('jar.position > 1')), '6'],
    'the builder it was handed' => [fn (): Sum => Sum::make()->query(static function (QueryBuilder $query): void {
        $query->andWhere('jar.position > 1');
    }), '6'],
    'a scope cleared' => [fn (): Sum => Sum::make()->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere('jar.position > 1'))->query(null), '7'],
]);

it('summarizes whichever builder it is pointed at last, its scope narrowing that one', function (): void {
    $this->seed(new LarderJar(position: 1), new LarderJar(position: 2), new LarderJar(position: 4));

    $pointed = $this->entityManager()->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar')->andWhere('jar.position < 4');
    $summarizer = Sum::make()->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere('jar.position > 1'));

    expect($this->renderSummarizer(Sum::make(), query: $pointed))->toContainElement(Selectors::summarizerFigure('text'), '3')
        ->and($this->renderSummarizer($summarizer, query: $pointed))->toContainElement(Selectors::summarizerFigure('text'), '2');
});
