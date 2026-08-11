<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Sylament\Support\Pagination\Paginator;
use Sylament\Tables\Columns\Column;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Gig;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;
use Sylament\Tables\Tests\Fixtures\Doctrine\RecordingMiddleware;

/**
 * Radiohead with two songs, Adele with none, Björk with one — paged two at a
 * time, so the first page carries both a record the batch answers for and one
 * it leaves absent.
 */
function artistsWithSongs(?RecordingMiddleware $recorder = null): EntityManager
{
    $entityManager = EntityManagerFactory::create($recorder);

    $radiohead = new Artist(1, 'Radiohead');
    $adele = new Artist(2, 'Adele');
    $bjork = new Artist(3, 'Björk');

    EntityManagerFactory::seed(
        $entityManager,
        $radiohead,
        $adele,
        $bjork,
        new Song(1, 'Airbag', 10, $radiohead),
        new Song(2, 'Let Down', 20, $radiohead),
        new Song(3, 'Jóga', 300, $bjork),
    );

    return $entityManager;
}

/**
 * @param array<Column> $columns
 */
function artistTableHost(EntityManager $entityManager, array $columns, int|string $recordsPerPage = 2): TableHost
{
    return new TableHost(function (Table $table) use ($entityManager, $columns, $recordsPerPage): Table {
        $table = $table
            ->query($entityManager->createQueryBuilder()->select('artist')->from(Artist::class, 'artist')->orderBy('artist.id', 'ASC'))
            ->columns($columns);

        return $recordsPerPage === 'all'
            ? $table->paginated([10, 'all'])->defaultPaginationPageOption('all')
            : $table->paginated([$recordsPerPage]);
    });
}

/**
 * The named column's cell state on every row of the resolved page.
 *
 * @return list<mixed>
 */
function aggregateCellStates(TableHost $host, string $columnName): array
{
    $records = $host->getTableRecords();
    $column = $host->getTable()->getColumn($columnName);
    $states = [];

    foreach ($records instanceof Paginator ? $records->getRecords() : $records as $recordKey => $record) {
        $states[] = $column?->record($record)->recordKey($recordKey)->getState();
    }

    return $states;
}

it('fills the store for the page and the cell reads it', function (): void {
    $host = artistTableHost(artistsWithSongs(), [TextColumn::make('songs_count')->counts('songs')]);

    expect(aggregateCellStates($host, 'songs_count'))->toBe([2, 0]);
});

it('exists reads as a real bool with false for absent keys', function (): void {
    $host = artistTableHost(artistsWithSongs(), [TextColumn::make('songs_exists')->exists('songs')]);

    expect(aggregateCellStates($host, 'songs_exists'))->toBe([true, false]);
});

it('avg reads null for a record with no related rows', function (): void {
    $host = artistTableHost(artistsWithSongs(), [TextColumn::make('songs_avg_plays')->avg('songs', 'plays')]);

    expect(aggregateCellStates($host, 'songs_avg_plays'))->toBe([15.0, null]);
});

it('adds no aggregate artifacts to the page or count queries', function (): void {
    $recorder = new RecordingMiddleware();
    $host = artistTableHost(artistsWithSongs($recorder), [TextColumn::make('songs_count')->counts('songs')]);

    $recorder->reset();

    $states = aggregateCellStates($host, 'songs_count');
    $statements = $recorder->statements();

    expect($states)->toBe([2, 0])
        ->and($statements)->toHaveCount(3)
        ->and($statements[0])->toContain('LIMIT')
        ->and($statements[0])->not->toContain('GROUP BY')
        ->and($statements[0])->not->toContain('COUNT(')
        ->and($statements[1])->toContain('GROUP BY')
        ->and($statements[2])->toContain('COUNT(')
        ->and($statements[2])->not->toContain('GROUP BY');
});

it('binds a declared scope closure through the column evaluator', function (): void {
    $scope = static fn (string $alias, QueryBuilder $query): QueryBuilder => $query->andWhere("{$alias}.plays > 100");
    $host = artistTableHost(artistsWithSongs(), [TextColumn::make('songs_count')->counts(['songs' => $scope])], recordsPerPage: 'all');

    expect(aggregateCellStates($host, 'songs_count'))->toBe([0, 0, 1]);
});

it('leaves an inert declaration to the record walk', function (): void {
    $recorder = new RecordingMiddleware();
    $host = artistTableHost(artistsWithSongs($recorder), [TextColumn::make('name')->counts(['songs as songs_count'])]);
    $column = $host->getTable()->getColumn('name');

    $recorder->reset();

    expect($column?->hasRelationshipAggregates())->toBeTrue()
        ->and($column?->getCellAggregateDeclaration())->toBeNull()
        ->and(aggregateCellStates($host, 'name'))->toBe(['Radiohead', 'Adele'])
        ->and($recorder->statements())->toHaveCount(2);
});

it('fills a table whose records carry an object identifier', function (): void {
    $entityManager = EntityManagerFactory::create();
    $arena = new Venue(new Handle('arena'), 'The Arena');
    $shed = new Venue(new Handle('shed'), 'The Shed');

    EntityManagerFactory::seed(
        $entityManager,
        $arena,
        $shed,
        new Gig(1, 'Opening', 400, $arena),
        new Gig(2, 'Encore', 900, $arena),
    );

    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('venue')->from(Venue::class, 'venue')->orderBy('venue.handle', 'ASC'))
        ->columns([TextColumn::make('gigs_count')->counts('gigs')])
        ->paginated(false));

    expect(aggregateCellStates($host, 'gigs_count'))->toBe([2, 0]);
});

it("fills on the 'all' per-page arm too", function (): void {
    $host = artistTableHost(artistsWithSongs(), [TextColumn::make('songs_count')->counts('songs')], recordsPerPage: 'all');

    expect(aggregateCellStates($host, 'songs_count'))->toBe([2, 0, 1]);
});
