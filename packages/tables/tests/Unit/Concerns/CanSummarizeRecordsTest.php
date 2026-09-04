<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Columns\Column;
use Sylament\Tables\Columns\Summarizers\Average;
use Sylament\Tables\Columns\Summarizers\Count;
use Sylament\Tables\Columns\Summarizers\Range;
use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tables\Columns\Summarizers\Values;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Address;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Book;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\CompositePair;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Genre;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Tables\Tests\Fixtures\Doctrine\RecordingMiddleware;

/**
 * Six songs summing to 210, in ascending play order across two artists — so a page
 * of the first two sums to 30 where the whole table sums to 210, and the two numbers
 * can never be mistaken for each other.
 */
function summaryStateSongs(?RecordingMiddleware $recorder = null): EntityManager
{
    $entityManager = EntityManagerFactory::create($recorder);

    $radiohead = new Artist(1, 'Radiohead', address: new Address('Abbey Road'));
    $bjork = new Artist(2, 'Björk', address: new Address('Laugavegur'));

    EntityManagerFactory::seed(
        $entityManager,
        $radiohead,
        $bjork,
        new Song(id: 1, title: 'Airbag', plays: 10, artist: $radiohead),
        new Song(id: 2, title: 'Let Down', plays: 20, artist: $radiohead),
        new Song(id: 3, title: 'No Surprises', plays: 30, artist: $radiohead),
        new Song(id: 4, title: 'Jóga', plays: 40, artist: $bjork),
        new Song(id: 5, title: 'Hyperballad', plays: 50, artist: $bjork),
        new Song(id: 6, title: 'Bachelorette', plays: 60, artist: $bjork),
    );

    return $entityManager;
}

/**
 * @param array<Column> $columns
 */
function summaryStateHost(EntityManager $entityManager, array $columns, int|string|null $recordsPerPage = 2, int $page = 1): TableHost
{
    $host = new TableHost(function (Table $table) use ($entityManager, $columns, $recordsPerPage): Table {
        $table = $table
            ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song')->orderBy('song.id', 'ASC'))
            ->columns($columns);

        return $recordsPerPage === null ? $table->paginated(false) : $table->paginated([$recordsPerPage]);
    });

    $host->tablePage = $page;

    return $host;
}

it('takes the all-table summary query from the filtered query, and reads it unsorted and unpaged', function (): void {
    $recorder = new RecordingMiddleware();
    $host = summaryStateHost(summaryStateSongs($recorder), [TextColumn::make('plays')->summarize(Sum::make())]);
    $query = $host->getAllTableSummaryQuery();

    $recorder->reset();

    expect($query)->toBeInstanceOf(QueryBuilder::class)
        ->and($query?->getMaxResults())->toBeNull()
        ->and($host->getTableSummarySelectedState($query))->toBe(['sy_summary_0' => 210])
        ->and($recorder->statements())->toHaveCount(1)
        ->and($recorder->statements()[0])->not->toContain('ORDER BY')
        ->and($recorder->statements()[0])->not->toContain('LIMIT');
});

it('summarizes only what the filters and the searches left', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [TextColumn::make('title')->searchable(), TextColumn::make('plays')->summarize(Sum::make())]);
    $host->tableSearch = 'Airbag';

    expect($host->getTableSummarySelectedState($host->getAllTableSummaryQuery()))->toBe(['sy_summary_0' => 10]);
});

/*
 * The one case that catches the trap the whole design turns on: a LIMIT bounds an
 * aggregate's result row rather than the aggregated set, so a page summary written
 * with one reads the all-table figure on every page and never errors.
 */
it('summarizes the page over the page keys, to a different figure than the whole table', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [TextColumn::make('plays')->summarize(Sum::make())]);

    expect($host->getTableSummarySelectedState($host->getPageTableSummaryQuery()))->toBe(['sy_summary_0' => 30])
        ->and($host->getTableSummarySelectedState($host->getAllTableSummaryQuery()))->toBe(['sy_summary_0' => 210]);
});

it('narrows the page summary to the keys of the page being shown', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [TextColumn::make('plays')->summarize(Sum::make())], page: 2);
    $query = $host->getPageTableSummaryQuery();

    expect($query?->getDQL())->toContain('IN (:sylament_summary_keys)')
        ->and($query?->getParameter('sylament_summary_keys')?->getValue())->toBe(['3', '4'])
        ->and($host->getTableSummarySelectedState($query))->toBe(['sy_summary_0' => 70]);
});

it('summarizes the whole table on a page holding all of it', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [TextColumn::make('plays')->summarize(Sum::make())], recordsPerPage: 'all');

    expect($host->getTableSummarySelectedState($host->getPageTableSummaryQuery()))->toBe(['sy_summary_0' => 210]);
});

/*
 * An empty page would bind an empty key list, and `IN ()` is a syntax error on most
 * platforms — the same guard the aggregate batch already carries.
 */
it('answers no page summary query for an empty page', function (): void {
    $host = summaryStateHost(EntityManagerFactory::create(), [TextColumn::make('plays')->summarize(Sum::make())]);

    expect($host->getPageTableSummaryQuery())->toBeNull()
        ->and($host->getTableSummarySelectedState(null))->toBe([]);
});

it('answers no page summary query for an unpaginated table', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [TextColumn::make('plays')->summarize(Sum::make())], recordsPerPage: null);

    expect($host->getPageTableSummaryQuery())->toBeNull()
        ->and($host->getAllTableSummaryQuery())->toBeInstanceOf(QueryBuilder::class);
});

it('names the record class own identifier in the page narrowing', function (): void {
    $entityManager = EntityManagerFactory::create();

    EntityManagerFactory::seed($entityManager, new Book('a', 'Dune'), new Book('b', 'Ubik'), new Book('c', 'Solaris'));

    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('book')->from(Book::class, 'book')->orderBy('book.code', 'ASC'))
        ->columns([TextColumn::make('title')->summarize(Count::make())])
        ->paginated([2]));

    expect($host->getPageTableSummaryQuery()?->getDQL())->toContain('book.code IN (:sylament_summary_keys)')
        ->and($host->getTableSummarySelectedState($host->getPageTableSummaryQuery()))->toBe(['sy_summary_0' => 2]);
});

/*
 * A composite identifier is refused a page earlier and louder than a summary: keying
 * the page's own records is what throws, so the page summary needs no arm of its own
 * — while the all-table row, which keys nothing, still reads its figure.
 */
it('leaves the composite identifier refusal to the page it cannot key', function (): void {
    $entityManager = EntityManagerFactory::create();

    EntityManagerFactory::seed($entityManager, new CompositePair(1, 1, 'first'), new CompositePair(1, 2, 'second'));

    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('pair')->from(CompositePair::class, 'pair')->orderBy('pair.a', 'ASC'))
        ->columns([TextColumn::make('label')->summarize(Count::make())])
        ->paginated([1]));

    expect($host->getTableSummarySelectedState($host->getAllTableSummaryQuery()))->toBe(['sy_summary_0' => 2])
        ->and(fn (): ?QueryBuilder => $host->getPageTableSummaryQuery())
        ->toThrow(LogicException::class, 'composite identifier');
});

it('answers no summary query at all on the records lane', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [['id' => '1', 'plays' => 10]])
        ->columns([TextColumn::make('plays')->summarize(Sum::make())]));

    expect($host->getAllTableSummaryQuery())->toBeNull()
        ->and($host->getPageTableSummaryQuery())->toBeNull();
});

/*
 * The batch is the reason the tier exists: every batchable summarizer of every visible
 * column rides one statement, each under its own deterministic alias.
 */
it('reads every batchable summarizer of every column in one statement', function (): void {
    $recorder = new RecordingMiddleware();
    $host = summaryStateHost(summaryStateSongs($recorder), [
        TextColumn::make('plays')->summarize([Sum::make(), Average::make()]),
        TextColumn::make('id')->summarize(Count::make()),
    ], recordsPerPage: 'all');

    $query = $host->getAllTableSummaryQuery();
    $recorder->reset();

    $state = $host->getTableSummarySelectedState($query);

    expect($state)->toBe(['sy_summary_0' => 210, 'sy_summary_1' => 35.0, 'sy_summary_2' => 6])
        ->and($recorder->statements())->toHaveCount(1)
        ->and($recorder->statements()[0])->not->toContain('GROUP BY');
});

it('hands each batched summarizer the alias its own figure comes back under', function (): void {
    $sum = Sum::make();
    $average = Average::make();
    $host = summaryStateHost(summaryStateSongs(), [TextColumn::make('plays')->summarize([$sum, $average])], recordsPerPage: 'all');

    $state = $host->getTableSummarySelectedState($host->getAllTableSummaryQuery());

    $sum->selectedState($state);
    $average->selectedState($state);

    expect($sum->getSelectedState())->toBe(210)
        ->and($average->getSelectedState())->toBe(35.0);
});

it('leaves a scoped summarizer out of the batch so it runs its own query', function (): void {
    $scoped = Count::make()->query(fn (QueryBuilder $query): QueryBuilder => $query->andWhere('song.plays > 30'));
    $host = summaryStateHost(summaryStateSongs(), [
        TextColumn::make('plays')->summarize([Sum::make(), $scoped]),
    ], recordsPerPage: 'all');

    $query = $host->getAllTableSummaryQuery();

    expect($host->getTableSummarySelectedState($query))->toBe(['sy_summary_0' => 210])
        ->and($scoped->query($query)->getState())->toBe(3);
});

it('batches nothing when no summarizer emits a select statement', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [
        TextColumn::make('plays')->summarize([Range::make(), Values::make()]),
    ], recordsPerPage: 'all');

    expect($host->getTableSummarySelectedState($host->getAllTableSummaryQuery()))->toBe([]);
});

it('reads a to-one relation column off one join it reuses', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [
        TextColumn::make('artist.name')->summarize(Count::make()),
        TextColumn::make('artist.id')->summarize(Count::make()),
    ], recordsPerPage: 'all');

    $query = $host->getAllTableSummaryQuery();
    $state = $host->getTableSummarySelectedState($query);

    expect($state)->toBe(['sy_summary_0' => 6, 'sy_summary_1' => 6])
        ->and(substr_count($query?->getDQL() ?? '', 'LEFT JOIN song.artist'))->toBe(1);
});

it('reads an embeddable path off the root alias', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [
        TextColumn::make('artist.address.street')->summarize(Count::make()),
    ], recordsPerPage: 'all');

    expect($host->getTableSummarySelectedState($host->getAllTableSummaryQuery()))->toBe(['sy_summary_0' => 6]);
});

it('refuses a column summarized through a to-many association', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [
        TextColumn::make('genres.name')->summarize(Count::make()),
    ], recordsPerPage: 'all');

    expect(fn (): array => $host->getTableSummarySelectedState($host->getAllTableSummaryQuery()))
        ->toThrow(LogicException::class, 'Cannot summarize [genres.name]');
});

/*
 * A to-many join multiplies the rows every aggregate on the statement reads, so a
 * user's own joining query is refused rather than answered with an inflated figure.
 */
it('refuses to summarize over a query that joins a to-many association', function (): void {
    $entityManager = summaryStateSongs();
    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song')->leftJoin('song.genres', 'sy_genres'))
        ->columns([TextColumn::make('plays')->summarize(Sum::make())]));

    expect(fn (): array => $host->getTableSummarySelectedState($host->getAllTableSummaryQuery()))
        ->toThrow(LogicException::class, 'Summarizer::using()');
});

it('leaves an aggregate column out of the batch, where its refusal is read time', function (): void {
    $entityManager = summaryStateSongs();

    EntityManagerFactory::seed($entityManager, new Genre(1, 'Rock'));

    $host = summaryStateHost($entityManager, [
        TextColumn::make('genres_count')->counts('genres')->summarize(Sum::make()),
        TextColumn::make('plays')->summarize(Sum::make()),
    ], recordsPerPage: 'all');

    expect($host->getTableSummarySelectedState($host->getAllTableSummaryQuery()))->toBe(['sy_summary_0' => 210]);
});

/*
 * The band renders the visible columns, so the batch reads them too — a toggled-off
 * column contributes no aggregate to a statement whose value nothing would read.
 */
it('batches the visible columns rather than every declared one', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [
        TextColumn::make('plays')->summarize(Sum::make())->hidden(),
        TextColumn::make('id')->summarize(Count::make()),
    ], recordsPerPage: 'all');

    expect($host->getTableSummarySelectedState($host->getAllTableSummaryQuery()))->toBe(['sy_summary_0' => 6]);
});

it('hands back an independent builder on every read', function (): void {
    $host = summaryStateHost(summaryStateSongs(), [TextColumn::make('plays')->summarize(Sum::make())]);

    $first = $host->getAllTableSummaryQuery();
    $second = $host->getAllTableSummaryQuery();

    $first?->andWhere('song.plays > 30');

    expect($first)->not->toBe($second)
        ->and($second?->getDQL())->not->toContain('song.plays > 30')
        ->and($host->getTableSummarySelectedState($second))->toBe(['sy_summary_0' => 210]);
});
