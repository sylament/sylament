<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Columns\Summarizers\Values;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;

/**
 * Four songs whose moods repeat and whose plays do not, so a distinct list is
 * shorter than the row count.
 */
function valuesRig(Values $summarizer, string $columnName = 'mood'): Values
{
    $entityManager = EntityManagerFactory::create();

    EntityManagerFactory::seed(
        $entityManager,
        new Song(id: 1, title: 'Airbag', plays: 10, mood: 'calm'),
        new Song(id: 2, title: 'Airport', plays: 20, mood: 'calm'),
        new Song(id: 3, title: 'Jóga', plays: 300, mood: 'bright'),
        new Song(id: 4, title: 'Lucky', plays: 40, mood: 'bright'),
    );

    $host = new TableHost(fn (Table $table): Table => $table->query(
        $entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song')->orderBy('song.title', 'ASC'),
    ));

    $summarizer
        ->column(TextColumn::make($columnName)->table(Table::make($host)))
        ->query($host->getFilteredTableQuery());

    return $summarizer;
}

it('renders through the values partial', function (): void {
    expect(Values::make()->getTemplate())->toBe('@SylamentTables/partials/Summarizer/values.html.twig');
});

it('reads the distinct values of the column as a flat list', function (): void {
    expect(valuesRig(Values::make())->getState())->toBe(['calm', 'bright'])
        ->and(valuesRig(Values::make(), 'plays')->getState())->toBe([10, 20, 300, 40]);
});

it('reads an empty set as an empty list', function (): void {
    $summarizer = valuesRig(Values::make());
    $query = $summarizer->getQuery();

    expect($query)->not->toBeNull();
    assert($query instanceof QueryBuilder);

    expect($summarizer->query($query->andWhere('song.id = 999'))->getState())->toBe([]);
});

it('is bulleted by default', function (): void {
    expect(Values::make()->isBulleted())->toBeTrue()
        ->and(Values::make()->bulleted(false)->isBulleted())->toBeFalse()
        ->and(Values::make()->bulleted(fn (): bool => false)->isBulleted())->toBeFalse();
});

it('emits no select statement, so it never batches', function (): void {
    expect(Values::make()->getSelectStatements('song.mood'))->toBe([])
        ->and(Values::make()->hasSelectedState())->toBeFalse();
});
