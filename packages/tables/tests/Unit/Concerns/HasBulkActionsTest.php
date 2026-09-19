<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Parameter;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Enums\PaginationMode;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\CompositePair;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Gig;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;
use Sylament\Tables\Tests\Fixtures\Doctrine\RecordingMiddleware;

/*
 * Covers the query half of the selection — the constraint `getSelectedTableRecordsQuery()`
 * composes and what the two resolution arms return through it. The records-lane
 * half and the live round trip stay in the Integration suite of the same name.
 */

function selectableSongs(): EntityManager
{
    $entityManager = EntityManagerFactory::create();

    $radiohead = new Artist(1, 'Radiohead');

    EntityManagerFactory::seed(
        $entityManager,
        $radiohead,
        new Song(1, 'Alpha', 10, $radiohead),
        new Song(2, 'Beta', 20, $radiohead),
        new Song(3, 'Gamma', 30),
    );

    return $entityManager;
}

/**
 * @param (Closure(Table): Table)|null $configure
 */
function selectionHost(EntityManager $entityManager, ?Closure $configure = null): TableHost
{
    return new TableHost(function (Table $table) use ($entityManager, $configure): Table {
        $table
            ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song')->orderBy('song.id'))
            ->columns([TextColumn::make('title')->searchable()]);

        return $configure instanceof Closure ? $configure($table) : $table;
    });
}

it('constrains the query to the selected keys', function (): void {
    $host = selectionHost(selectableSongs());

    $host->selectedTableRecordKeys = ['1', '3'];

    expect(array_map(
        static fn (Song $song): string => $song->getTitle(),
        $host->getSelectedTableRecords(),
    ))->toBe(['Alpha', 'Gamma'])
        ->and($host->getSelectedTableRecordsQuery()->getDQL())
        ->toContain('song.id IN (:sylament_selected_record_keys)');
});

it('constrains the query away from the deselected keys while tracking', function (): void {
    $host = selectionHost(selectableSongs());
    $keyHost = selectionHost(selectableSongs());

    foreach ([$host, $keyHost] as $tracking) {
        $tracking->isTrackingDeselectedTableRecords = true;
        $tracking->deselectedTableRecords = ['2'];
    }

    expect(array_map(
        static fn (Song $song): string => $song->getTitle(),
        $host->getSelectedTableRecords(),
    ))->toBe(['Alpha', 'Gamma'])
        ->and($keyHost->getSelectedTableRecords(shouldFetchSelectedRecords: false))->toBe(['1', '3'])
        ->and($host->getSelectedTableRecordsQuery()->getDQL())
        ->toContain('song.id NOT IN (:sylament_selected_record_keys)');
});

/*
 * The default select-all path: the controller flips tracking on and empties both
 * bags, so the deselection bag is empty and the selection is *everything*. An
 * emitted `NOT IN ()` would invert to nothing at all — Doctrine expands an empty
 * list parameter to NULL — so the constraint has to be omitted instead.
 */
it('selects every filtered row while tracking an empty deselection bag', function (): void {
    $host = selectionHost(selectableSongs());
    $keyHost = selectionHost(selectableSongs());

    foreach ([$host, $keyHost] as $tracking) {
        $tracking->isTrackingDeselectedTableRecords = true;
        $tracking->deselectedTableRecords = [];
    }

    expect($host->hasSelectedTableRecords())->toBeTrue()
        ->and($host->getSelectedTableRecords())->toHaveCount(3)
        ->and($keyHost->getSelectedTableRecords(shouldFetchSelectedRecords: false))->toBe(['1', '2', '3'])
        ->and($host->getSelectedTableRecordsQuery()->getDQL())->not->toContain('NOT IN');
});

/*
 * The tracking flag is the one client value whose forgery *widens* the reach, so
 * it is re-derived against the same gate the checkboxes render from: a page-only
 * query table and a table that cannot track at all both resolve a forged flag as
 * the plain key selection rather than as every filtered row.
 */
it('refuses a tracking flag the table would never have offered', function (): void {
    $pageOnly = selectionHost(
        selectableSongs(),
        static fn (Table $table): Table => $table->selectCurrentPageOnly(),
    );
    $untracked = selectionHost(
        selectableSongs(),
        static fn (Table $table): Table => $table->deselectAllRecordsWhenFiltered(false),
    );

    foreach ([$pageOnly, $untracked] as $forged) {
        $forged->isTrackingDeselectedTableRecords = true;
        $forged->deselectedTableRecords = [];
        $forged->selectedTableRecordKeys = ['1'];
    }

    expect($pageOnly->getSelectedTableRecords(shouldFetchSelectedRecords: false))->toBe(['1'])
        ->and($pageOnly->isTableRecordSelected('2'))->toBeFalse()
        ->and($pageOnly->getSelectedTableRecordsQuery()->getDQL())
        ->toContain('song.id IN (:sylament_selected_record_keys)')
        ->and($untracked->getSelectedTableRecords(shouldFetchSelectedRecords: false))->toBe(['1'])
        ->and($untracked->isTableRecordSelected('2'))->toBeFalse()
        ->and($untracked->getSelectedTableRecordsQuery()->getDQL())
        ->toContain('song.id IN (:sylament_selected_record_keys)');
});

it('reports nothing selected for a forged tracking flag over an empty selection', function (): void {
    $host = selectionHost(
        selectableSongs(),
        static fn (Table $table): Table => $table->selectCurrentPageOnly(),
    );

    $host->isTrackingDeselectedTableRecords = true;

    expect($host->hasSelectedTableRecords())->toBeFalse()
        ->and($host->getSelectedTableRecords())->toBe([]);
});

it('plucks the keys instead of hydrating when told not to fetch', function (): void {
    $host = selectionHost(selectableSongs());

    $host->selectedTableRecordKeys = ['3', '1'];

    expect($host->getSelectedTableRecords(shouldFetchSelectedRecords: false))->toBe(['1', '3']);
});

it('constrains the non-fetching query to the selectable records', function (): void {
    $host = selectionHost(
        selectableSongs(),
        static fn (Table $table): Table => $table->checkIfRecordIsSelectableUsing(
            static fn (Song $record): bool => $record->getId() !== 2,
        ),
    );
    $queryHost = selectionHost(
        selectableSongs(),
        static fn (Table $table): Table => $table->checkIfRecordIsSelectableUsing(
            static fn (Song $record): bool => $record->getId() !== 2,
        ),
    );

    foreach ([$host, $queryHost] as $selecting) {
        $selecting->selectedTableRecordKeys = ['1', '2', '3'];
    }

    expect($host->getSelectedTableRecords(shouldFetchSelectedRecords: false))->toBe(['1', '3'])
        ->and($queryHost->getSelectedTableRecordsQuery(shouldFetchSelectedRecords: false)->getDQL())
        ->toContain('song.id IN (:sylament_selectable_record_keys)')
        ->and($queryHost->getSelectedTableRecordsQuery()->getDQL())
        ->not->toContain(':sylament_selectable_record_keys');
});

it('re-applies the filters to the selection only where the table deselects on filtering', function (): void {
    $deselecting = selectionHost(selectableSongs());
    $keeping = selectionHost(
        selectableSongs(),
        static fn (Table $table): Table => $table->deselectAllRecordsWhenFiltered(false),
    );

    foreach ([$deselecting, $keeping] as $host) {
        $host->selectedTableRecordKeys = ['1', '2'];
        $host->tableSearch = 'Alpha';
    }

    expect($deselecting->getSelectedTableRecords(shouldFetchSelectedRecords: false))->toBe(['1'])
        ->and($keeping->getSelectedTableRecords(shouldFetchSelectedRecords: false))->toBe(['1', '2'])
        ->and($deselecting->getSelectedTableRecordsQuery()->getDQL())->toContain('LIKE')
        ->and($keeping->getSelectedTableRecordsQuery()->getDQL())->not->toContain('LIKE');
});

it('eager-loads the columns relations only on the fetching arm', function (): void {
    $host = selectionHost(
        selectableSongs(),
        static fn (Table $table): Table => $table->columns([TextColumn::make('artist.name')]),
    );

    $host->selectedTableRecordKeys = ['1'];

    expect($host->getSelectedTableRecordsQuery()->getDQL())->toContain('JOIN song.artist')
        ->and($host->getSelectedTableRecordsQuery(shouldFetchSelectedRecords: false)->getDQL())
        ->not->toContain('JOIN');
});

it('enumerates every filtered key as the selectable set, callback or not', function (): void {
    $host = selectionHost(selectableSongs());
    $selectable = selectionHost(
        selectableSongs(),
        static fn (Table $table): Table => $table->checkIfRecordIsSelectableUsing(
            static fn (Song $record): bool => $record->getId() !== 2,
        ),
    );

    expect($host->getAllSelectableTableRecordKeys())->toBe(['1', '2', '3'])
        ->and($selectable->getAllSelectableTableRecordKeys())->toBe(['1', '3']);
});

it('counts the paginator total as the selection ceiling', function (): void {
    $host = selectionHost(selectableSongs(), static fn (Table $table): Table => $table->paginated([2]));
    $selectable = selectionHost(
        selectableSongs(),
        static fn (Table $table): Table => $table
            ->paginated([2])
            ->checkIfRecordIsSelectableUsing(static fn (Song $record): bool => $record->getId() !== 2),
    );

    expect($host->getAllSelectableTableRecordsCount())->toBe(3)
        ->and($selectable->getAllSelectableTableRecordsCount())->toBe(2);
});

/*
 * Simple pagination is the one query-lane shape whose paginator carries no total,
 * and the ceiling is Filament's last arm rather than a null: a count over the
 * filtered query.
 */
it('counts the filtered query where simple pagination carries no total', function (): void {
    $host = selectionHost(
        selectableSongs(),
        static fn (Table $table): Table => $table->paginated([2])->paginationMode(PaginationMode::Simple),
    );

    expect($host->getTableRecords()->getTotalRecordCount())->toBeNull()
        ->and($host->getAllSelectableTableRecordsCount())->toBe(3);
});

/*
 * The grouped enumeration behind a group header's checkbox: the keys of one
 * group, taken from the whole filtered set or from the page alone, and keyed
 * through `Group::getStringKey()` on both lanes — the reader the header rendered
 * its own key with, so the two cannot disagree.
 */

/**
 * @param (Closure(Table): Table)|null $configure
 */
function groupedSelectionHost(EntityManager $entityManager, ?Closure $configure = null): TableHost
{
    return selectionHost($entityManager, function (Table $table) use ($configure): Table {
        $table->groups(['plays', 'artist.name'])->paginated([2]);

        return $configure instanceof Closure ? $configure($table) : $table;
    });
}

it('enumerates every key of one group across the pages', function (): void {
    $host = groupedSelectionHost(selectableSongs());
    $host->tableGrouping = 'artist.name';

    expect($host->getGroupedSelectableTableRecordKeys('Radiohead'))->toBe(['1', '2'])
        ->and($host->getGroupedSelectableTableRecordKeys(null))->toBe(['3']);
});

it('enumerates only the page of a group where selection is page-only', function (): void {
    $host = groupedSelectionHost(
        selectableSongs(),
        fn (Table $table): Table => $table->paginated([1])->selectCurrentPageOnly(),
    );
    $host->tableGrouping = 'artist.name';

    // Radiohead holds two songs across the filtered set and one on this page,
    // and the group whose key is blank holds none of it.
    expect(array_keys($host->getTable()->getRecords()))->toBe([1])
        ->and($host->getGroupedSelectableTableRecordKeys('Radiohead'))->toBe(['1'])
        ->and($host->getGroupedSelectableTableRecordKeys(null))->toBe([]);
});

it('re-applies the selectability callback to a grouped enumeration on both lanes', function (): void {
    $configure = static fn (Table $table): Table => $table->checkIfRecordIsSelectableUsing(
        static fn (Song $record): bool => $record->getId() !== 2,
    );

    $wholeSet = groupedSelectionHost(selectableSongs(), $configure);
    $wholeSet->tableGrouping = 'artist.name';

    $pageOnly = groupedSelectionHost(
        selectableSongs(),
        static fn (Table $table): Table => $configure($table)->selectCurrentPageOnly(),
    );
    $pageOnly->tableGrouping = 'plays';

    expect($wholeSet->getGroupedSelectableTableRecordKeys('Radiohead'))->toBe(['1'])
        ->and($pageOnly->getGroupedSelectableTableRecordKeys('20'))->toBe([]);
});

it('enumerates nothing for a group the table does not offer, or for none at all', function (): void {
    $grouped = groupedSelectionHost(selectableSongs());
    $grouped->tableGrouping = 'artist.name';

    $ungrouped = groupedSelectionHost(selectableSongs());

    expect($grouped->getGroupedSelectableTableRecordKeys('Nobody'))->toBe([])
        ->and($ungrouped->getGroupedSelectableTableRecordKeys('Radiohead'))->toBe([]);
});

it('memoises a grouped enumeration per group for the request', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);
    $radiohead = new Artist(1, 'Radiohead');

    EntityManagerFactory::seed(
        $entityManager,
        $radiohead,
        new Song(1, 'Alpha', 10, $radiohead),
        new Song(2, 'Beta', 20, $radiohead),
    );

    $host = groupedSelectionHost($entityManager);
    $host->tableGrouping = 'artist.name';

    $recorder->reset();

    $host->getGroupedSelectableTableRecordKeys('Radiohead');
    $host->getGroupedSelectableTableRecordKeys('Radiohead');

    expect($recorder->statements())->toHaveCount(1)
        ->and($host->getGroupedSelectableTableRecordKeys('Radiohead'))->toBe(['1', '2']);
});

it('forgets a grouped enumeration wherever the selection memo is dropped', function (string $method): void {
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);
    $radiohead = new Artist(1, 'Radiohead');

    EntityManagerFactory::seed($entityManager, $radiohead, new Song(1, 'Alpha', 10, $radiohead));

    $host = groupedSelectionHost($entityManager);
    $host->tableGrouping = 'artist.name';

    $host->getGroupedSelectableTableRecordKeys('Radiohead');

    $host->{$method}();

    // The clearer runs queries of its own, so the count is taken after it and
    // the assertion is that the *next* read adds to it rather than answering
    // from a memo the clearer should have dropped.
    $afterClearing = count($recorder->statements());

    $host->getGroupedSelectableTableRecordKeys('Radiohead');

    expect(count($recorder->statements()))->toBeGreaterThan($afterClearing)
        ->and($host->getGroupedSelectableTableRecordKeys('Radiohead'))->toBe(['1']);
})->with(['flushCachedTableRecords', 'deselectAllTableRecords', 'selectAllTableRecords']);

/*
 * The composite half of the same seam. Three flavours, one rig each: a two-integer
 * key (CompositePair), a two-string key whose components carry `.`, `/`, a space and
 * a `~` (Shortcut), and a derived identity whose first component is an association
 * onto a Handle-keyed Venue (Residency). Every key below is the string the codec
 * mints, which is what the client would have sent back.
 */

function selectablePairs(): EntityManager
{
    $entityManager = EntityManagerFactory::create();

    EntityManagerFactory::seed(
        $entityManager,
        new CompositePair(1, 2, 'one-two'),
        new CompositePair(1, 3, 'one-three'),
        new CompositePair(2, 1, 'two-one'),
    );

    return $entityManager;
}

function selectionShortcut(string $host, string $path, string $label, int $position): Shortcut
{
    $shortcut = new Shortcut();
    $shortcut->setHost($host);
    $shortcut->setPath($path);
    $shortcut->setLabel($label);
    $shortcut->setPosition($position);

    return $shortcut;
}

function selectableShortcuts(?RecordingMiddleware $recorder = null): EntityManager
{
    $entityManager = EntityManagerFactory::create($recorder);

    EntityManagerFactory::seed(
        $entityManager,
        selectionShortcut('www.example.com', '/blog/hello world', 'Blog', 1),
        selectionShortcut('www.example.com', '/about', 'About', 2),
        selectionShortcut('shop.example.com', '/sale~2026', 'Sale', 3),
    );

    return $entityManager;
}

function selectionResidency(Venue $venue, string $season, string $label, int $position): Residency
{
    $residency = new Residency();
    $residency->setVenue($venue);
    $residency->setSeason($season);
    $residency->setLabel($label);
    $residency->setPosition($position);

    return $residency;
}

function selectableResidencies(): EntityManager
{
    $entityManager = EntityManagerFactory::create();
    $venue = new Venue(new Handle('main-hall'), 'Main Hall');

    EntityManagerFactory::seed(
        $entityManager,
        $venue,
        selectionResidency($venue, '2026-spring', 'Spring', 1),
        selectionResidency($venue, 'autumn/2026', 'Autumn', 2),
    );

    return $entityManager;
}

/**
 * @param (Closure(Table): Table)|null $configure
 */
function pairSelectionHost(EntityManager $entityManager, ?Closure $configure = null): TableHost
{
    return new TableHost(function (Table $table) use ($entityManager, $configure): Table {
        $table
            ->query($entityManager->createQueryBuilder()
                ->select('compositePair')
                ->from(CompositePair::class, 'compositePair')
                ->orderBy('compositePair.a')
                ->addOrderBy('compositePair.b'))
            ->columns([TextColumn::make('label')]);

        return $configure instanceof Closure ? $configure($table) : $table;
    });
}

/**
 * @param (Closure(Table): Table)|null $configure
 */
function shortcutSelectionHost(EntityManager $entityManager, ?Closure $configure = null): TableHost
{
    return new TableHost(function (Table $table) use ($entityManager, $configure): Table {
        $table
            ->query($entityManager->createQueryBuilder()
                ->select('shortcut')
                ->from(Shortcut::class, 'shortcut')
                ->orderBy('shortcut.position'))
            ->columns([TextColumn::make('label')]);

        return $configure instanceof Closure ? $configure($table) : $table;
    });
}

/**
 * @param (Closure(Table): Table)|null $configure
 */
function residencySelectionHost(EntityManager $entityManager, ?Closure $configure = null): TableHost
{
    return new TableHost(function (Table $table) use ($entityManager, $configure): Table {
        $table
            ->query($entityManager->createQueryBuilder()
                ->select('residency')
                ->from(Residency::class, 'residency')
                ->orderBy('residency.position'))
            ->columns([TextColumn::make('label')]);

        return $configure instanceof Closure ? $configure($table) : $table;
    });
}

it('constrains a composite query to the selected keys', function (): void {
    $pairs = pairSelectionHost(selectablePairs());
    $residencies = residencySelectionHost(selectableResidencies());

    $pairs->selectedTableRecordKeys = ['1~2', '2~1'];
    $residencies->selectedTableRecordKeys = ['main-hall~autumn=2F2026'];

    expect(array_map(
        static fn (CompositePair $pair): string => $pair->getLabel(),
        $pairs->getSelectedTableRecords(),
    ))->toBe(['one-two', 'two-one'])
        ->and($pairs->getSelectedTableRecordsQuery()->getDQL())
        ->toContain('(compositePair.a = :sylament_selected_record_keys_0_0 AND compositePair.b = :sylament_selected_record_keys_0_1) OR (compositePair.a = :sylament_selected_record_keys_1_0 AND compositePair.b = :sylament_selected_record_keys_1_1)')
        ->and(array_map(
            static fn (Residency $residency): string => $residency->getLabel(),
            $residencies->getSelectedTableRecords(),
        ))->toBe(['Autumn'])
        ->and($residencies->getSelectedTableRecordsQuery()->getDQL())
        ->toContain('residency.venue = :sylament_selected_record_keys_0_0 AND residency.season = :sylament_selected_record_keys_0_1');
});

it('constrains a composite query away from the deselected keys while tracking', function (): void {
    $pairs = pairSelectionHost(selectablePairs());
    $residencies = residencySelectionHost(selectableResidencies());

    foreach ([$pairs, $residencies] as $tracking) {
        $tracking->isTrackingDeselectedTableRecords = true;
    }

    $pairs->deselectedTableRecords = ['1~3'];
    $residencies->deselectedTableRecords = ['main-hall~2026-spring'];

    expect(array_map(
        static fn (CompositePair $pair): string => $pair->getLabel(),
        $pairs->getSelectedTableRecords(),
    ))->toBe(['one-two', 'two-one'])
        ->and($pairs->getSelectedTableRecordsQuery()->getDQL())
        ->toContain('NOT ((compositePair.a = :sylament_selected_record_keys_0_0 AND compositePair.b = :sylament_selected_record_keys_0_1))')
        ->and(array_map(
            static fn (Residency $residency): string => $residency->getLabel(),
            $residencies->getSelectedTableRecords(),
        ))->toBe(['Autumn'])
        ->and($residencies->getSelectedTableRecordsQuery()->getDQL())
        ->toContain('NOT ((residency.venue = :sylament_selected_record_keys_0_0 AND residency.season = :sylament_selected_record_keys_0_1))');
});

/*
 * The composite twin of the empty-deselection case above: with nothing to exclude the
 * predicate is omitted entirely, because an OR-of-ANDs built from no key at all would
 * have to be `NOT (FALSE)` — and the shape it replaces, `NOT IN ()`, expanded to NULL
 * and matched nothing.
 */
it('selects every row of a composite table while tracking an empty deselection bag', function (): void {
    $host = pairSelectionHost(selectablePairs());

    $host->isTrackingDeselectedTableRecords = true;
    $host->deselectedTableRecords = [];

    expect($host->hasSelectedTableRecords())->toBeTrue()
        ->and($host->getSelectedTableRecords())->toHaveCount(3)
        ->and($host->getSelectedTableRecordsQuery()->getDQL())->not->toContain('sylament_selected_record_keys');
});

/*
 * A key is client input and the decode is total: a wrong arity, an uppercase-hex
 * violation and a non-canonical integer all name no record rather than raising. A
 * selection left with none becomes `1 = 0`; a deselection left with none is nothing to
 * exclude, which is every row.
 */
it('drops a malformed key from a composite selection rather than refusing it', function (): void {
    $selecting = pairSelectionHost(selectablePairs());
    $empty = pairSelectionHost(selectablePairs());
    $tracking = pairSelectionHost(selectablePairs());

    $selecting->selectedTableRecordKeys = ['1~2', 'garbage', '1~2~3', '01~2'];
    $empty->selectedTableRecordKeys = ['garbage'];
    $tracking->isTrackingDeselectedTableRecords = true;
    $tracking->deselectedTableRecords = ['garbage'];

    expect(array_map(
        static fn (CompositePair $pair): string => $pair->getLabel(),
        $selecting->getSelectedTableRecords(),
    ))->toBe(['one-two'])
        ->and($empty->getSelectedTableRecords())->toBe([])
        ->and($empty->getSelectedTableRecordsQuery()->getDQL())->toContain('1 = 0')
        ->and($tracking->getSelectedTableRecords())->toHaveCount(3)
        ->and($tracking->getSelectedTableRecordsQuery()->getDQL())->not->toContain('sylament_selected_record_keys');
});

/*
 * Two key predicates on one builder — the selection and the selectability narrowing —
 * which is the collision `setParameter()` would have made in silence. Each application
 * mints its names off its own caller-supplied base, so the assertion is on the parameter
 * names rather than on the rows alone.
 */
it('narrows a composite non-fetching query to the selectable records without colliding', function (): void {
    $host = pairSelectionHost(
        selectablePairs(),
        static fn (Table $table): Table => $table->checkIfRecordIsSelectableUsing(
            static fn (CompositePair $record): bool => $record->getB() !== 3,
        ),
    );

    $host->selectedTableRecordKeys = ['1~2', '1~3', '2~1'];

    $query = $host->getSelectedTableRecordsQuery(shouldFetchSelectedRecords: false);

    expect($query->getDQL())
        ->toContain('(compositePair.a = :sylament_selectable_record_keys_0_0 AND compositePair.b = :sylament_selectable_record_keys_0_1)')
        ->and(array_values(array_map(
            static fn (Parameter $parameter): string => $parameter->getName(),
            $query->getParameters()->toArray(),
        )))->toBe([
            'sylament_selected_record_keys_0_0',
            'sylament_selected_record_keys_0_1',
            'sylament_selected_record_keys_1_0',
            'sylament_selected_record_keys_1_1',
            'sylament_selected_record_keys_2_0',
            'sylament_selected_record_keys_2_1',
            'sylament_selectable_record_keys_0_0',
            'sylament_selectable_record_keys_0_1',
            'sylament_selectable_record_keys_1_0',
            'sylament_selectable_record_keys_1_1',
        ])
        ->and(array_map(
            static fn (CompositePair $pair): string => $pair->getLabel(),
            $query->getQuery()->getResult(),
        ))->toBe(['one-two', 'two-one'])
        ->and($host->getSelectedTableRecordsQuery()->getDQL())->not->toContain('sylament_selectable_record_keys');
});

/*
 * Under `fetchSelectedRecords(false)` the answer is keys rather than records, and for a
 * composite table those keys cannot come off one column: the pluck selects every
 * identifier field under a deterministic alias, encodes each row, and de-duplicates
 * *after* encoding, since a join could otherwise repeat a row.
 */
it('plucks encoded keys instead of hydrating a composite table', function (): void {
    $recorder = new RecordingMiddleware();
    $shortcuts = shortcutSelectionHost(selectableShortcuts($recorder));
    $residencies = residencySelectionHost(selectableResidencies());

    foreach ([$shortcuts, $residencies] as $tracking) {
        $tracking->isTrackingDeselectedTableRecords = true;
    }

    $recorder->reset();

    expect($shortcuts->getSelectedTableRecords(shouldFetchSelectedRecords: false))->toBe([
        'www=2Eexample=2Ecom~=2Fblog=2Fhello=20world',
        'www=2Eexample=2Ecom~=2Fabout',
        'shop=2Eexample=2Ecom~=2Fsale=7E2026',
    ])
        // The keys come off the identifier columns alone: one statement, aliased
        // per identifier field, carrying no other column of the record — a
        // hydrating read answers the same strings and costs the whole row.
        ->and($recorder->statements())->toHaveCount(1)
        ->and($recorder->statements()[0])
        ->toContain('.host AS')
        ->toContain('.path AS')
        ->not->toContain('label')
        ->and($shortcuts->getAllSelectableTableRecordKeys())->toBe([
            'www=2Eexample=2Ecom~=2Fblog=2Fhello=20world',
            'www=2Eexample=2Ecom~=2Fabout',
            'shop=2Eexample=2Ecom~=2Fsale=7E2026',
        ])
        ->and($residencies->getSelectedTableRecords(shouldFetchSelectedRecords: false))->toBe([
            'main-hall~2026-spring',
            'main-hall~autumn=2F2026',
        ]);
});

/*
 * The same pluck behind both narrowings at once: a key the decode refuses never reaches
 * the query, a well-formed key naming no row answers nothing, and a row the table
 * refuses to let the user select is cut by the selectable predicate rather than by PHP.
 */
it('plucks only the well-formed selectable keys of a composite table', function (): void {
    $host = shortcutSelectionHost(
        selectableShortcuts(),
        static fn (Table $table): Table => $table->checkIfRecordIsSelectableUsing(
            static fn (Shortcut $record): bool => $record->getHost() === 'www.example.com',
        ),
    );

    $host->selectedTableRecordKeys = [
        'www=2Eexample=2Ecom~=2Fblog=2Fhello=20world',
        'shop=2Eexample=2Ecom~=2Fsale=7E2026',
        'www=2Eexample=2Ecom~=2Fnope',
        'garbage',
    ];

    expect($host->getSelectedTableRecords(shouldFetchSelectedRecords: false))
        ->toBe(['www=2Eexample=2Ecom~=2Fblog=2Fhello=20world']);
});

function joinedResidencies(): EntityManager
{
    $entityManager = EntityManagerFactory::create();
    $venue = new Venue(new Handle('main-hall'), 'Main Hall');

    EntityManagerFactory::seed(
        $entityManager,
        $venue,
        new Gig(1, 'Opening', 100, $venue),
        new Gig(2, 'Closing', 200, $venue),
        selectionResidency($venue, '2026-spring', 'Spring', 1),
    );

    return $entityManager;
}

function joinedResidencySelectionHost(EntityManager $entityManager): TableHost
{
    return new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()
            ->select('residency')
            ->from(Residency::class, 'residency')
            ->leftJoin('residency.venue', 'sy_venue')
            ->leftJoin('sy_venue.gigs', 'sy_gigs')
            ->orderBy('residency.position'))
        ->columns([TextColumn::make('label')]));
}

/*
 * The de-duplication is the pluck's own and has to happen *after* encoding: a join onto a
 * to-many repeats the root row once per joined row, so the same identifier comes back
 * twice and an un-deduplicated list would hand DeleteBulkAction a doubled key — a wrong
 * success count and an inflated chunk count. The venue below carries two gigs and one
 * residency, so the key query returns two rows for one record.
 */
it('plucks one key per composite row even when the query joins a to-many', function (): void {
    $host = joinedResidencySelectionHost(joinedResidencies());

    $host->isTrackingDeselectedTableRecords = true;

    expect($host->getSelectedTableRecords(shouldFetchSelectedRecords: false))->toBe(['main-hall~2026-spring']);
});
