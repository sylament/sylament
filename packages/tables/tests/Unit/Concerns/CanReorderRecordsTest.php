<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;
use Sylament\Tables\Tests\Fixtures\Doctrine\RecordingMiddleware;

/**
 * Three positioned songs on the query lane, reorderable by `position` unless the
 * caller says otherwise.
 *
 * @return array{TableHost, EntityManager}
 */
function reorderRig(?Closure $configure = null): array
{
    $entityManager = EntityManagerFactory::create();

    EntityManagerFactory::seed(
        $entityManager,
        new Song(1, 'Airbag', 100, position: 1),
        new Song(2, 'Bones', 200, position: 2),
        new Song(3, 'Creep', 300, position: 3),
    );

    $host = new TableHost(fn (Table $table): Table => ($configure ?? static fn (Table $table): Table => $table)(
        $table
            ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
            ->reorderable('position'),
    ));

    return [$host, $entityManager];
}

/**
 * The stored position of every song, keyed by identifier, read past the identity
 * map so a write that never reached the database shows up as the old value.
 *
 * @return array<int, int|null>
 */
function storedPositions(EntityManager $entityManager): array
{
    $entityManager->clear();

    $positions = [];

    foreach ($entityManager->getRepository(Song::class)->findBy([], ['id' => 'ASC']) as $song) {
        $positions[$song->getId()] = $song->getPosition();
    }

    return $positions;
}

it('numbers the submitted order 1..N', function (): void {
    [$host, $entityManager] = reorderRig();

    $host->reorderTable(['3', '1', '2']);

    expect(storedPositions($entityManager))->toBe([1 => 2, 2 => 3, 3 => 1]);
});

it('reverses before numbering when the direction is descending', function (): void {
    [$host, $entityManager] = reorderRig(fn (Table $table): Table => $table->reorderable('position', direction: 'desc'));

    $host->reorderTable(['3', '1', '2']);

    expect(storedPositions($entityManager))->toBe([1 => 2, 2 => 1, 3 => 3]);
});

it('leaves a gap where a key the query does not answer for sat, numbering the rest', function (): void {
    [$host, $entityManager] = reorderRig(fn (Table $table): Table => $table
        ->modifyQueryUsing(fn (QueryBuilder $query): QueryBuilder => $query->andWhere('song.id != 2')));

    $host->reorderTable(['3', '2', '1']);

    expect(storedPositions($entityManager))->toBe([1 => 3, 2 => 2, 3 => 1]);
});

it('ignores non-scalar keys the client smuggled in', function (): void {
    [$host, $entityManager] = reorderRig();

    $host->reorderTable(['3', ['1'], '1']);

    expect(storedPositions($entityManager))->toBe([1 => 2, 2 => 2, 3 => 1]);
});

it('writes nothing when the table is not reorderable', function (): void {
    [$host, $entityManager] = reorderRig(fn (Table $table): Table => $table->reorderable('position', false));

    $host->reorderTable(['3', '2', '1']);

    expect(storedPositions($entityManager))->toBe([1 => 1, 2 => 2, 3 => 3]);
});

it('refuses to reorder a records() table, naming the lanes that can', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table
        ->records(fn (): array => [['__key' => '1']])
        ->reorderable('position'));

    expect(fn (): mixed => $host->reorderTable(['1']))
        ->toThrow(LogicException::class, 'reorders records it does not query');
});

it('brackets the write with the table hooks', function (): void {
    $seen = [];

    [$host, $entityManager] = reorderRig(function (Table $table) use (&$seen): Table {
        return $table
            ->beforeReordering(function (array $order) use (&$seen): void {
                $seen['before'] = $order;
            })
            ->afterReordering(function (array $order) use (&$seen): void {
                $seen['after'] = $order;
            });
    });

    $host->reorderTable(['2', '1', '3']);

    expect($seen)->toBe(['before' => ['2', '1', '3'], 'after' => ['2', '1', '3']])
        ->and(storedPositions($entityManager))->toBe([1 => 2, 2 => 1, 3 => 3]);
});

it('orders by the reorder column alone while reordering, bypassing both sorts', function (): void {
    [$host] = reorderRig(fn (Table $table): Table => $table
        ->columns([TextColumn::make('title')->sortable()])
        ->defaultSort('plays', 'desc')
        ->reorderable('position', direction: 'desc'));

    $host->tableSort = 'title:asc';

    expect($host->getFilteredSortedTableQuery()?->getDQL())->toContain('ORDER BY song.title asc');

    $host->toggleTableReordering();

    expect($host->getFilteredSortedTableQuery()?->getDQL())->toBe(
        'SELECT song FROM ' . Song::class . ' song ORDER BY song.position desc',
    );
});

it('fetches every row while reordering unless the table paginates through it', function (): void {
    [$unpaginated] = reorderRig(fn (Table $table): Table => $table->paginated([2]));
    [$paginated] = reorderRig(fn (Table $table): Table => $table->paginated([2])->paginatedWhileReordering());

    expect($unpaginated->getTableRecords())->toHaveCount(2)
        ->and($unpaginated->getTablePaginator())->not->toBeNull();

    $unpaginated->toggleTableReordering();
    $paginated->toggleTableReordering();

    expect($unpaginated->getTableRecords())->toHaveCount(3)
        ->and($unpaginated->getTablePaginator())->toBeNull()
        ->and($paginated->getTableRecords())->toHaveCount(2)
        ->and($paginated->getTable()->isPaginatedWhileReordering())->toBeTrue();
});

it('toggles the reordering flag, gated by the definition', function (): void {
    [$reorderable] = reorderRig();
    [$unreorderable] = reorderRig(fn (Table $table): Table => $table->reorderable());

    $reorderable->toggleTableReordering();
    $unreorderable->toggleTableReordering();

    expect($reorderable->isTableReordering)->toBeTrue()
        ->and($reorderable->isTableReordering())->toBeTrue()
        ->and($unreorderable->isTableReordering)->toBeTrue()
        ->and($unreorderable->isTableReordering())->toBeFalse();

    $reorderable->toggleTableReordering();

    expect($reorderable->isTableReordering())->toBeFalse();
});

/*
 * The same drag, keyed compositely: the payload is still the strings the rows were
 * stamped with, and every one is re-resolved through the table's own query before a
 * position is written.
 */

function reorderResidency(Venue $venue, string $season, string $label, int $position): Residency
{
    $residency = new Residency();
    $residency->setVenue($venue);
    $residency->setSeason($season);
    $residency->setLabel($label);
    $residency->setPosition($position);

    return $residency;
}

/**
 * The scope closure is handed the very builder `getQuery()` answers with, which
 * the reorder then narrows a **copy** of, once per chunk — so capturing it there
 * is how a test reads back that the builder it was handed came through untouched.
 *
 * @param (Closure(QueryBuilder): void)|null $captureQuery
 *
 * @return array{TableHost, EntityManager, RecordingMiddleware}
 */
function residencyReorderRig(?Closure $captureQuery = null): array
{
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);
    $venue = new Venue(new Handle('main-hall'), 'Main Hall');

    EntityManagerFactory::seed(
        $entityManager,
        $venue,
        reorderResidency($venue, '2026-spring', 'Spring', 1),
        reorderResidency($venue, 'autumn/2026', 'Autumn', 2),
    );

    $host = new TableHost(function (Table $table) use ($entityManager, $captureQuery): Table {
        $table
            ->query($entityManager->createQueryBuilder()->select('residency')->from(Residency::class, 'residency'))
            ->reorderable('position');

        if ($captureQuery instanceof Closure) {
            $table->modifyQueryUsing(static function (QueryBuilder $query) use ($captureQuery): QueryBuilder {
                $captureQuery($query);

                return $query;
            });
        }

        return $table;
    });

    $recorder->reset();

    return [$host, $entityManager, $recorder];
}

/**
 * Every statement the recorder holds that narrows by a key list — a SELECT
 * carrying bound parameters. The reorder's own reads are the only ones on these
 * rigs: the table query is unfiltered and the write is an UPDATE.
 *
 * @return list<string>
 */
function narrowingReorderSelects(RecordingMiddleware $recorder): array
{
    return array_values(array_filter(
        $recorder->statements(),
        static fn (string $sql): bool => str_starts_with($sql, 'SELECT') && str_contains($sql, '?'),
    ));
}

function reorderShortcut(string $host, string $path, string $label, int $position): Shortcut
{
    $shortcut = new Shortcut();
    $shortcut->setHost($host);
    $shortcut->setPath($path);
    $shortcut->setLabel($label);
    $shortcut->setPosition($position);

    return $shortcut;
}

/**
 * @return array{TableHost, EntityManager}
 */
function shortcutReorderRig(): array
{
    $entityManager = EntityManagerFactory::create();

    EntityManagerFactory::seed(
        $entityManager,
        reorderShortcut('www.example.com', '/blog/hello world', 'Blog', 1),
        reorderShortcut('www.example.com', '/about', 'About', 2),
        reorderShortcut('shop.example.com', '/sale~2026', 'Sale', 3),
    );

    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('shortcut')->from(Shortcut::class, 'shortcut'))
        ->reorderable('position'));

    return [$host, $entityManager];
}

/**
 * @return array<string, int>
 */
function storedResidencyPositions(EntityManager $entityManager): array
{
    $entityManager->clear();

    $positions = [];

    foreach ($entityManager->getRepository(Residency::class)->findBy([], ['season' => 'ASC']) as $residency) {
        $positions[$residency->getSeason()] = $residency->getPosition();
    }

    return $positions;
}

/**
 * @return array<string, int>
 */
function storedShortcutPositions(EntityManager $entityManager): array
{
    $entityManager->clear();

    $positions = [];

    foreach ($entityManager->getRepository(Shortcut::class)->findBy([], ['path' => 'ASC']) as $shortcut) {
        $positions[$shortcut->getPath()] = $shortcut->getPosition();
    }

    return $positions;
}

it('numbers a derived-identity order 1..N, off a copy of the query it was handed', function (): void {
    $reorderQuery = null;

    [$host, $entityManager, $recorder] = residencyReorderRig(function (QueryBuilder $query) use (&$reorderQuery): void {
        $reorderQuery = $query;
    });

    $host->reorderTable(['main-hall~autumn=2F2026', 'main-hall~2026-spring']);

    $narrowed = narrowingReorderSelects($recorder);

    expect(storedResidencyPositions($entityManager))->toBe(['2026-spring' => 2, 'autumn/2026' => 1])
        ->and($narrowed)->toHaveCount(1)
        ->and($narrowed[0])->toMatch('/venue_id = \? AND \w+\.season = \?\) OR \(/')
        ->and(mb_substr_count($narrowed[0], '?'))->toBe(4)
        ->and($reorderQuery)->toBeInstanceOf(QueryBuilder::class)
        ->and($reorderQuery?->getDQL())->not->toContain('sylament_reordered_record_keys');
});

/*
 * Reordering is unpaginated by default, so one drag submits the whole table and the
 * re-resolution is the widest read a composite table takes: 501 two-field rows is the
 * cheapest order that is more than one statement, since `IdentifierConstraint::chunk()`
 * splits a two-field list at min(500, intdiv(2000, 2)) = 500. Two things fail silently
 * without the copy: chunk two ANDed onto chunk one answers nothing, and a per-chunk
 * parameter base would end in an index, which `assertParameterBase()` refuses outright.
 * The key the trap leaves behind is `/p/1` — the order runs 501 down to 1, so that one
 * row is chunk two on its own, and it is the position asserted below.
 *
 * @return array{TableHost, EntityManager, RecordingMiddleware}
 */
function chunkedShortcutReorderRig(int $count): array
{
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);
    $shortcuts = [];

    for ($index = 1; $index <= $count; $index++) {
        $shortcuts[] = reorderShortcut('bulk.test', '/p/' . $index, 'Bulk ' . $index, $index);
    }

    EntityManagerFactory::seed($entityManager, ...$shortcuts);

    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('shortcut')->from(Shortcut::class, 'shortcut'))
        ->reorderable('position'));

    $recorder->reset();

    return [$host, $entityManager, $recorder];
}

it('resolves a reordered order wider than one chunk in one statement per chunk', function (): void {
    [$host, $entityManager, $recorder] = chunkedShortcutReorderRig(501);

    $order = [];

    for ($index = 501; $index >= 1; $index--) {
        $order[] = 'bulk=2Etest~=2Fp=2F' . $index;
    }

    $host->reorderTable($order);

    $narrowed = narrowingReorderSelects($recorder);
    $placeholders = array_map(static fn (string $sql): int => mb_substr_count($sql, '?'), $narrowed);
    $positions = storedShortcutPositions($entityManager);

    expect($narrowed)->toHaveCount(2)
        ->and(max($placeholders))->toBeLessThanOrEqual(1000)
        ->and(array_sum($placeholders))->toBe(1002)
        ->and($positions)->toHaveCount(501)
        ->and($positions['/p/501'])->toBe(1)
        ->and($positions['/p/1'])->toBe(501);
});

it('leaves a gap where a malformed key sat in a two-string order', function (): void {
    [$host, $entityManager] = shortcutReorderRig();

    $host->reorderTable(['shop=2Eexample=2Ecom~=2Fsale=7E2026', 'garbage', 'www=2Eexample=2Ecom~=2Fabout']);

    expect(storedShortcutPositions($entityManager))->toBe([
        '/about' => 3,
        '/blog/hello world' => 1,
        '/sale~2026' => 1,
    ]);
});
