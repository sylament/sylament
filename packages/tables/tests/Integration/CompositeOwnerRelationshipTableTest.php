<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Closure;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\Relationship;
use Sylament\Support\Tests\Doctrine\TestPlatform;
use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Circuit;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\CircuitStop;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\ResidencyRider;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\ShortcutNote;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

use function Sylament\Tables\Tests\renderToHtml;

uses(InteractsWithLiveComponents::class);

/*
 * A relation-scoped table whose OWNER is keyed by more than an `id`: a scalar
 * pair, an association plus a scalar, and a record of the same class on both
 * sides of a self-reference. Every arm such an owner can hold is here — the
 * one-to-many, the owning many-to-many, its inverse side, and both of those
 * onto a target that is itself keyed by several fields — and on top of them the
 * whole table feature list, because the narrowing is a `WHERE` string and every
 * reader downstream is supposed to be blind to it.
 *
 * Two things make "lists this owner's records" able to fail, and the seed
 * carries both wherever the arm allows one. A near-miss owner holding records
 * of its own shares one half of the key — the FIRST half on `notes`, `riders`,
 * `children` and `shortcuts`, the SECOND half on `troupes` (Marquee, which
 * shares Zenith's path rather than its host), and none at all on `billedBy`,
 * whose only other owner shares neither — so a scope that compares one field
 * lists the sibling's records too; and `ShortcutNote` and `Circuit` each hold a
 * record belonging to nobody (the note `Delta`, the circuit `Loose`), so a
 * scope dropped altogether lists that one.
 *
 * Keys are written as literals, never minted through the codec in an
 * expectation. The three `Circuit` children sort by name against both their
 * identifier order and their seed order, so the mapping's
 * `#[ORM\OrderBy(['name' => SortDirection::Descending])]` is visible in every unsorted list, and
 * two of them carry the same stop count, so a sort by that count falls through
 * to the mapping order, which settles the tie, with the composite root's own
 * identifier order appended behind it.
 */

/**
 * The query-lane rig: one in-memory database holding every owner flavour, built
 * outside the container, so the association reader the `#[Required]` setter
 * would have injected is set by hand — the `relationshipTable()` precedent in
 * `tests/Pest.php`, which this file cannot use because it needs the entity
 * manager back.
 */
function compositeOwnerTableRig(): EntityManager
{
    $entityManager = EntityManagerFactory::create();

    $circuit = static function (string $region, string $slot, string $name, int $position): Circuit {
        $circuit = new Circuit();
        $circuit->setRegion($region);
        $circuit->setSlot($slot);
        $circuit->setName($name);
        $circuit->setPosition($position);

        return $circuit;
    };

    $root = $circuit('north', 'one', 'Root', 5);
    $sibling = $circuit('north', 'nine', 'Sibling', 9);
    $alpha = $circuit('north', 'two', 'Alpha', 2);
    $bravo = $circuit('south', 'two', 'Bravo', 1);
    $charlie = $circuit('north', 'four', 'Charlie', 3);
    $delta = $circuit('west', 'four', 'Delta', 4);
    $loose = $circuit('east', 'three', 'Loose', 7);

    $root->addChild($alpha);
    $root->addChild($bravo);
    $root->addChild($charlie);

    $sibling->addChild($delta);

    $alpha->setRelated($loose);
    $bravo->setRelated($sibling);
    $charlie->setRelated($root);

    $shortcut = static function (string $host, string $path, string $label): Shortcut {
        $shortcut = new Shortcut();
        $shortcut->setHost($host);
        $shortcut->setPath($path);
        $shortcut->setLabel($label);

        return $shortcut;
    };

    $zenith = $shortcut('plain', 'about', 'Zenith');
    $beacon = $shortcut('plain', 'contact', 'Beacon');
    $marquee = $shortcut('spare', 'about', 'Marquee');

    $north = new Venue(new Handle('north.hall'), 'North Hall');
    $astra = new Venue(new Handle('astra-hall'), 'Astra Hall');

    $residency = static function (Venue $venue, string $season, string $label): Residency {
        $residency = new Residency();
        $residency->setVenue($venue);
        $residency->setSeason($season);
        $residency->setLabel($label);

        return $residency;
    };

    $summer = $residency($north, 'summer-2026', 'Summer');
    $encore = $residency($north, 'winter-2027', 'Encore');
    $winter = $residency($astra, 'winter-2026', 'Winter');

    $ada = new Artist(1, 'Ada');
    $bob = new Artist(2, 'Bob');

    foreach ([
        $root, $sibling, $alpha, $bravo, $charlie, $delta, $loose,
        $zenith, $beacon, $marquee,
        $north, $astra, $summer, $encore, $winter,
        $ada, $bob,
        new CircuitStop(1, 'Ridge', $alpha),
        new CircuitStop(2, 'Basin', $alpha),
        new CircuitStop(3, 'Crest', $bravo),
        new CircuitStop(4, 'Dune', $charlie),
        new CircuitStop(5, 'Ridge', $loose),
        new ShortcutNote(1, 'Beta', 2, $zenith),
        new ShortcutNote(2, 'Alpha', 1, $zenith),
        new ShortcutNote(3, 'Gamma', 3, $beacon),
        new ShortcutNote(4, 'Delta', 4),
        new ResidencyRider(1, 'Lights', 1, $summer),
        new ResidencyRider(2, 'Sound', 2, $winter),
        new ResidencyRider(3, 'Rigging', 3, $encore),
    ] as $entity) {
        $entityManager->persist($entity);
    }

    $zenith->addTroupe($ada);
    $marquee->addTroupe($bob);
    $ada->addShortcut($beacon);
    $bob->addShortcut($marquee);
    $root->addShortcut($zenith);
    $root->addShortcut($beacon);

    $sibling->addShortcut($marquee);

    $entityManager->flush();
    $entityManager->clear();

    return $entityManager;
}

function compositeOwnerTableCircuit(EntityManager $entityManager, string $region, string $slot): Circuit
{
    $circuit = $entityManager->find(Circuit::class, ['region' => $region, 'slot' => $slot]);
    assert($circuit instanceof Circuit);

    return $circuit;
}

function compositeOwnerTableShortcut(EntityManager $entityManager, string $host, string $path): Shortcut
{
    $shortcut = $entityManager->find(Shortcut::class, ['host' => $host, 'path' => $path]);
    assert($shortcut instanceof Shortcut);

    return $shortcut;
}

function compositeOwnerTableResidency(EntityManager $entityManager, string $venue, string $season): Residency
{
    $residency = $entityManager->find(Residency::class, ['venue' => $entityManager->find(Venue::class, new Handle($venue)), 'season' => $season]);
    assert($residency instanceof Residency);

    return $residency;
}

/**
 * A relation-scoped table on a host that is not a live component, so the state
 * every feature reads — the sort, the search bags, the selection, the page, the
 * grouping — is set as the property it is.
 */
function compositeOwnerTableHost(EntityManager $entityManager, object $owner, string $name, ?Closure $configure = null): TableHost
{
    $host = new TableHost(fn (Table $table): Table => ($configure ?? static fn (Table $table): Table => $table)(
        $table->relationship(static fn (): Relationship => new Relationship($owner, $name)),
    ));

    $table = $host->getTable();
    $table->setAssociationReader(new AssociationReader($entityManager, $table->getPropertyAccessor()));

    return $host;
}

/**
 * @return list<string>
 */
function compositeOwnerTableKeys(TableHost $host): array
{
    return array_map(strval(...), array_keys($host->getTable()->getRecords()));
}

/**
 * The `Circuit` column set every depth case shares: a searchable field, a
 * searchable to-many hop, a relationship count, and the two dotted to-one hops
 * whose first segment is the name of an alias the owner subselect already holds.
 */
function compositeOwnerTableCircuitColumns(Table $table): Table
{
    return $table->columns([
        TextColumn::make('name')->searchable(isIndividual: true)->sortable(),
        TextColumn::make('stops.label')->searchable(isIndividual: true)->bulleted(),
        TextColumn::make('stops_count')->counts('stops')->sortable(),
        TextColumn::make('owner.name')->sortable(),
        TextColumn::make('related.name')->sortable(),
    ]);
}

/**
 * The same seed as {@see compositeOwnerTableRig()} in the container's entity
 * manager, for the live hosts. Reboot is disabled for `CompositeKeyTableTest`'s
 * reason: the fixture database is an in-memory SQLite held by a container
 * service, so a reboot between two live requests would hand the second an empty
 * one.
 *
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function compositeOwnerTableSeededClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $zenith = new Shortcut();
    $zenith->setHost('plain');
    $zenith->setPath('about');
    $zenith->setLabel('Zenith');

    $beacon = new Shortcut();
    $beacon->setHost('plain');
    $beacon->setPath('contact');
    $beacon->setLabel('Beacon');

    $north = new Venue(new Handle('north.hall'), 'North Hall');

    $summer = new Residency();
    $summer->setVenue($north);
    $summer->setSeason('summer-2026');
    $summer->setLabel('Summer');

    $encore = new Residency();
    $encore->setVenue($north);
    $encore->setSeason('winter-2027');
    $encore->setLabel('Encore');

    foreach ([
        $zenith, $beacon, $north, $summer, $encore,
        new ShortcutNote(1, 'Beta', 2, $zenith),
        new ShortcutNote(2, 'Alpha', 1, $zenith),
        new ShortcutNote(3, 'Gamma', 3, $beacon),
        new ShortcutNote(4, 'Delta', 4),
        new ResidencyRider(1, 'Lights', 1, $summer),
        new ResidencyRider(2, 'Rigging', 2, $encore),
    ] as $entity) {
        $entityManager->persist($entity);
    }

    $entityManager->flush();
    $entityManager->clear();

    return [$client, $entityManager];
}

/**
 * The record rows' keys as the markup carries them, read off the one attribute
 * every record row has — never `.sy-ta-row`, which a summary row and a group
 * header row wear too.
 *
 * @return list<string>
 */
function compositeOwnerTableRenderedKeys(Crawler $crawler): array
{
    return $crawler->filter('tbody tr[id*=".table.records."]')->each(
        static fn (Crawler $row): string => mb_substr((string) $row->attr('id'), mb_strpos((string) $row->attr('id'), '.table.records.') + mb_strlen('.table.records.')),
    );
}

it('lists exactly the records of one owner on every arm a record of several fields can own', function (): void {
    $entityManager = compositeOwnerTableRig();
    $zenith = compositeOwnerTableShortcut($entityManager, 'plain', 'about');
    $root = compositeOwnerTableCircuit($entityManager, 'north', 'one');

    expect(compositeOwnerTableKeys(compositeOwnerTableHost($entityManager, $zenith, 'notes')))->toBe(['2', '1'])
        ->and(compositeOwnerTableKeys(compositeOwnerTableHost($entityManager, $zenith, 'troupes')))->toBe(['1'])
        ->and(compositeOwnerTableKeys(compositeOwnerTableHost($entityManager, compositeOwnerTableShortcut($entityManager, 'plain', 'contact'), 'billedBy')))->toBe(['1'])
        ->and(compositeOwnerTableKeys(compositeOwnerTableHost($entityManager, compositeOwnerTableResidency($entityManager, 'north.hall', 'summer-2026'), 'riders')))->toBe(['1'])
        ->and(compositeOwnerTableKeys(compositeOwnerTableHost($entityManager, $root, 'children')))->toBe(['north~four', 'south~two', 'north~two'])
        ->and(compositeOwnerTableKeys(compositeOwnerTableHost($entityManager, $root, 'shortcuts')))->toBe(['plain~about', 'plain~contact']);
});

it('lists none of the records of an owner that shares one half of its key', function (): void {
    $entityManager = compositeOwnerTableRig();

    expect(compositeOwnerTableKeys(compositeOwnerTableHost($entityManager, compositeOwnerTableShortcut($entityManager, 'plain', 'contact'), 'notes')))->toBe(['3'])
        ->and(compositeOwnerTableKeys(compositeOwnerTableHost($entityManager, compositeOwnerTableCircuit($entityManager, 'north', 'nine'), 'children')))->toBe(['west~four'])
        ->and(compositeOwnerTableKeys(compositeOwnerTableHost($entityManager, compositeOwnerTableCircuit($entityManager, 'north', 'nine'), 'shortcuts')))->toBe(['spare~about'])
        ->and(compositeOwnerTableKeys(compositeOwnerTableHost($entityManager, compositeOwnerTableResidency($entityManager, 'north.hall', 'winter-2027'), 'riders')))->toBe(['3']);
});

/*
 * The leading `WHERE ` is part of every expectation: `toContain('EXISTS')` alone
 * passes against the negated predicate too.
 */
it('scopes every arm by one correlated subselect bound per identifier field of the owner', function (): void {
    $entityManager = compositeOwnerTableRig();
    $zenith = compositeOwnerTableShortcut($entityManager, 'plain', 'about');
    $root = compositeOwnerTableCircuit($entityManager, 'north', 'one');

    $dql = static fn (TableHost $host): string => (string) $host->getTable()->getRelationshipQuery()?->getDQL();
    $parameters = static fn (TableHost $host): array => array_map(
        static fn (object $parameter): array => [$parameter->getName(), $parameter->getValue()],
        iterator_to_array($host->getTable()->getRelationshipQuery()?->getParameters() ?? []),
    );
    $aliases = static fn (TableHost $host): array => $host->getTable()->getRelationshipQuery()?->getAllAliases() ?? [];

    expect($dql(compositeOwnerTableHost($entityManager, $zenith, 'notes')))
        ->toContain('WHERE EXISTS (SELECT 1 FROM ' . Shortcut::class . ' sy_owner JOIN sy_owner.notes sy_related WHERE sy_owner.host = :sylament_relationship_owner_0 AND sy_owner.path = :sylament_relationship_owner_1 AND sy_related.id = record.id)')
        ->and($dql(compositeOwnerTableHost($entityManager, $zenith, 'troupes')))
        ->toContain('WHERE EXISTS (SELECT 1 FROM ' . Shortcut::class . ' sy_owner JOIN sy_owner.troupes sy_related WHERE sy_owner.host = :sylament_relationship_owner_0 AND sy_owner.path = :sylament_relationship_owner_1 AND sy_related.id = record.id)')
        ->and($dql(compositeOwnerTableHost($entityManager, compositeOwnerTableShortcut($entityManager, 'plain', 'contact'), 'billedBy')))
        ->toContain('WHERE EXISTS (SELECT 1 FROM ' . Shortcut::class . ' sy_owner JOIN sy_owner.billedBy sy_related WHERE sy_owner.host = :sylament_relationship_owner_0 AND sy_owner.path = :sylament_relationship_owner_1 AND sy_related.id = record.id)')
        ->and($dql(compositeOwnerTableHost($entityManager, compositeOwnerTableResidency($entityManager, 'north.hall', 'summer-2026'), 'riders')))
        ->toContain('WHERE EXISTS (SELECT 1 FROM ' . Residency::class . ' sy_owner JOIN sy_owner.riders sy_related WHERE sy_owner.venue = :sylament_relationship_owner_0 AND sy_owner.season = :sylament_relationship_owner_1 AND sy_related.id = record.id)')
        ->and($dql(compositeOwnerTableHost($entityManager, $root, 'children')))
        ->toContain('WHERE EXISTS (SELECT 1 FROM ' . Circuit::class . ' sy_owner JOIN sy_owner.children sy_related WHERE sy_owner.region = :sylament_relationship_owner_0 AND sy_owner.slot = :sylament_relationship_owner_1 AND sy_related.region = record.region AND sy_related.slot = record.slot)')
        ->and($dql(compositeOwnerTableHost($entityManager, $root, 'shortcuts')))
        ->toContain('WHERE EXISTS (SELECT 1 FROM ' . Circuit::class . ' sy_owner JOIN sy_owner.shortcuts sy_related WHERE sy_owner.region = :sylament_relationship_owner_0 AND sy_owner.slot = :sylament_relationship_owner_1 AND sy_related.host = record.host AND sy_related.path = record.path)')
        // The owner binds one parameter per identifier field, and an association
        // half binds its target's own identifier rather than the entity.
        ->and($parameters(compositeOwnerTableHost($entityManager, $root, 'children')))
        ->toBe([['sylament_relationship_owner_0', 'north'], ['sylament_relationship_owner_1', 'one']])
        ->and($parameters(compositeOwnerTableHost($entityManager, compositeOwnerTableResidency($entityManager, 'north.hall', 'summer-2026'), 'riders')))
        ->toBe([['sylament_relationship_owner_0', 'north.hall'], ['sylament_relationship_owner_1', 'summer-2026']])
        // The outer builder carries one root and no join of its own — on every
        // arm, not only on the one the subselect correlates by two columns. A
        // `toContain()` over the DQL text would pass against a joined builder.
        ->and(array_map($aliases, [
            compositeOwnerTableHost($entityManager, $zenith, 'notes'),
            compositeOwnerTableHost($entityManager, $zenith, 'troupes'),
            compositeOwnerTableHost($entityManager, compositeOwnerTableShortcut($entityManager, 'plain', 'contact'), 'billedBy'),
            compositeOwnerTableHost($entityManager, compositeOwnerTableResidency($entityManager, 'north.hall', 'summer-2026'), 'riders'),
            compositeOwnerTableHost($entityManager, $root, 'children'),
            compositeOwnerTableHost($entityManager, $root, 'shortcuts'),
        ]))->toBe([['record'], ['record'], ['record'], ['record'], ['record'], ['record']]);
});

it('takes the mapping order as its default sort and appends the identifier order behind it', function (): void {
    $entityManager = compositeOwnerTableRig();
    $root = compositeOwnerTableCircuit($entityManager, 'north', 'one');

    $sorted = compositeOwnerTableHost($entityManager, $root, 'children', compositeOwnerTableCircuitColumns(...));
    $sorted->tableSort = 'stops_count:desc';

    // Bravo and Charlie both carry one stop, so the mapping's own `name DESC` is
    // what settles them; the composite root's identifier order rides behind it,
    // which is what the ordering text pins and the three rows cannot.
    expect(compositeOwnerTableKeys(compositeOwnerTableHost($entityManager, $root, 'children', compositeOwnerTableCircuitColumns(...))))->toBe(['north~four', 'south~two', 'north~two'])
        ->and(compositeOwnerTableKeys($sorted))->toBe(['north~two', 'north~four', 'south~two'])
        ->and((string) $sorted->getFilteredSortedTableQuery()?->getDQL())
        ->toContain('ORDER BY sy_agg1 DESC, record.name DESC, record.region DESC, record.slot DESC');
});

it('counts a relation of the related records and sorts the scoped page by that count', function (): void {
    $entityManager = compositeOwnerTableRig();
    $host = compositeOwnerTableHost($entityManager, compositeOwnerTableCircuit($entityManager, 'north', 'one'), 'children', compositeOwnerTableCircuitColumns(...));

    $crawler = new Crawler(renderToHtml($host->getTable()));
    $counts = $crawler->filter('tbody tr[id*=".table.records."]')->each(
        static fn (Crawler $row): string => mb_trim($row->filter('td[id$=".column.stops_count"]')->text()),
    );

    expect(compositeOwnerTableRenderedKeys($crawler))->toBe(['north~four', 'south~two', 'north~two'])
        ->and($counts)->toBe(['1', '1', '2']);
});

/*
 * The search subselect is minted inside a query whose `WHERE` already carries
 * the owner's, so the two live side by side in one flat alias namespace, and the
 * parameter-counted `sy_x<n>` stem is offset by the owner's own parameters.
 */
it('narrows the scoped page by a relation search and by a per-column search, nesting one subselect inside the other', function (): void {
    $entityManager = compositeOwnerTableRig();
    $root = compositeOwnerTableCircuit($entityManager, 'north', 'one');

    $searched = compositeOwnerTableHost($entityManager, $root, 'children', compositeOwnerTableCircuitColumns(...));
    $searched->tableSearch = 'Ridge';

    $foreign = compositeOwnerTableHost($entityManager, $root, 'children', compositeOwnerTableCircuitColumns(...));
    $foreign->tableSearch = 'Delta';

    $byColumn = compositeOwnerTableHost($entityManager, $root, 'children', compositeOwnerTableCircuitColumns(...));
    $byColumn->tableColumnSearches = ['stops' => ['label' => 'Crest']];

    $dql = (string) $searched->getFilteredTableQuery()?->getDQL();

    // 'Ridge' also labels a stop of a circuit outside this owner's children, and
    // 'Delta' names one of the sibling owner's, so both terms can reach a record
    // the scope must keep out.
    expect(compositeOwnerTableKeys($searched))->toBe(['north~two'])
        ->and(compositeOwnerTableKeys($foreign))->toBe([])
        ->and(compositeOwnerTableKeys($byColumn))->toBe(['south~two'])
        ->and($dql)->toContain('WHERE (EXISTS (SELECT 1 FROM ' . Circuit::class . ' sy_owner JOIN sy_owner.children sy_related')
        // The inner subselect's alias stem is minted from the parameter count,
        // which the owner's two parameters offset — `sy_x3` where a
        // single-identifier owner would mint `sy_x2`. Re-read this literal when a
        // parameter moves; never re-run it blind.
        ->and($dql)->toContain(match (TestPlatform::current()) {
            TestPlatform::PostgreSql => 'EXISTS (SELECT sy_x3.region FROM ' . Circuit::class . ' sy_x3 JOIN sy_x3.stops sy_x3_0 WHERE sy_x3.region = record.region AND sy_x3.slot = record.slot AND LOWER(sy_x3_0.label) LIKE :sylament_search_3)',
            TestPlatform::Sqlite, TestPlatform::MySql => 'EXISTS (SELECT sy_x3.region FROM ' . Circuit::class . ' sy_x3 JOIN sy_x3.stops sy_x3_0 WHERE sy_x3.region = record.region AND sy_x3.slot = record.slot AND sy_x3_0.label LIKE :sylament_search_3)',
        });
});

it('resolves a selection of the scoped relation in both strategies, on one builder with the owner', function (): void {
    $entityManager = compositeOwnerTableRig();
    $root = compositeOwnerTableCircuit($entityManager, 'north', 'one');

    $explicit = compositeOwnerTableHost($entityManager, $root, 'children', compositeOwnerTableCircuitColumns(...));
    // 'west~four' is the sibling owner's child: a well-formed key of the target
    // class this owner's relation never offered.
    $explicit->selectedTableRecordKeys = ['north~two', 'west~four'];

    $tracking = compositeOwnerTableHost($entityManager, $root, 'children', compositeOwnerTableCircuitColumns(...));
    $tracking->isTrackingDeselectedTableRecords = true;
    $tracking->deselectedTableRecords = ['north~two'];

    $plucked = compositeOwnerTableHost($entityManager, $root, 'children', compositeOwnerTableCircuitColumns(...));
    $plucked->selectedTableRecordKeys = ['north~two', 'west~four'];

    $names = static fn (array $records): array => array_map(static fn (Circuit $record): string => $record->getName(), $records);
    $parameters = array_map(
        static fn (object $parameter): string => $parameter->getName(),
        iterator_to_array($explicit->getSelectedTableRecordsQuery()->getParameters()),
    );

    expect($names($explicit->getSelectedTableRecords()))->toBe(['Alpha'])
        ->and($names($tracking->getSelectedTableRecords()))->toEqualCanonicalizing(['Charlie', 'Bravo'])
        ->and($plucked->getSelectedTableRecords(false))->toBe(['north~two'])
        ->and($parameters)->toBe([
            'sylament_relationship_owner_0',
            'sylament_relationship_owner_1',
            'sylament_selected_record_keys_0_0',
            'sylament_selected_record_keys_0_1',
            'sylament_selected_record_keys_1_0',
            'sylament_selected_record_keys_1_1',
        ]);
});

it('narrows the enumerated keys of the scoped relation by the selectability callback', function (): void {
    $entityManager = compositeOwnerTableRig();

    $host = compositeOwnerTableHost(
        $entityManager,
        compositeOwnerTableCircuit($entityManager, 'north', 'one'),
        'children',
        static fn (Table $table): Table => compositeOwnerTableCircuitColumns($table)->checkIfRecordIsSelectableUsing(static fn (Circuit $record): bool => $record->getName() !== 'Bravo'),
    );

    $host->isTrackingDeselectedTableRecords = true;

    // The enumeration plucks off the filtered query, which carries no ordering of
    // its own, so it answers a set rather than the rendered order.
    expect($host->getAllSelectableTableRecordKeys())->toEqualCanonicalizing(['north~four', 'north~two'])
        ->and($host->getAllSelectableTableRecordsCount())->toBe(2);
});

/*
 * A keyed root whose query joins a collection pages through the two-step read
 * rather than Doctrine's paginator, and here the root is keyed by two fields
 * while the base query already carries the owner's two parameters.
 */
it('pages the scoped relation through its identifiers when the query joins a collection', function (): void {
    $entityManager = compositeOwnerTableRig();
    $root = compositeOwnerTableCircuit($entityManager, 'north', 'one');

    $joined = static fn (Table $table): Table => $table
        ->columns([TextColumn::make('name'), TextColumn::make('stops.label')->bulleted()])
        ->modifyQueryUsing(static fn (QueryBuilder $query): QueryBuilder => $query->addSelect('stop')->leftJoin('record.stops', 'stop'))
        ->paginated([1]);

    $second = compositeOwnerTableHost($entityManager, $root, 'children', $joined);
    $second->tablePage = 3;

    $crawler = new Crawler(renderToHtml($second->getTable()));
    $bullets = $crawler->filter('tbody tr[id*=".table.records."] .sy-bulleted li')->each(static fn (Crawler $item): string => mb_trim($item->text()));

    expect(compositeOwnerTableKeys(compositeOwnerTableHost($entityManager, $root, 'children', $joined)))->toBe(['north~four'])
        // A page of one record still hydrates that record's whole collection,
        // which only the two-step read produces.
        ->and(compositeOwnerTableRenderedKeys($crawler))->toBe(['north~two'])
        ->and($bullets)->toEqualCanonicalizing(['Ridge', 'Basin'])
        ->and(mb_trim($crawler->filter('.sy-pagination-overview')->first()->text()))->toBe('Showing 3 to 3 of 3 results');
});

it('lists every record of the scoped relation on the all page option', function (): void {
    $entityManager = compositeOwnerTableRig();
    $root = compositeOwnerTableCircuit($entityManager, 'north', 'one');

    $all = compositeOwnerTableHost($entityManager, $root, 'children', static fn (Table $table): Table => compositeOwnerTableCircuitColumns($table)->paginated([1, 'all']));
    $all->tableRecordsPerPage = 'all';

    $one = compositeOwnerTableHost($entityManager, $root, 'children', static fn (Table $table): Table => compositeOwnerTableCircuitColumns($table)->paginated([1, 'all']));
    $one->tableRecordsPerPage = '1';

    expect(compositeOwnerTableKeys($all))->toBe(['north~four', 'south~two', 'north~two'])
        ->and(compositeOwnerTableKeys($one))->toBe(['north~four']);
});

it('summarizes the whole scoped relation and each of its groups', function (): void {
    $entityManager = compositeOwnerTableRig();

    $host = compositeOwnerTableHost(
        $entityManager,
        compositeOwnerTableCircuit($entityManager, 'north', 'one'),
        'children',
        static fn (Table $table): Table => $table
            ->columns([TextColumn::make('name'), TextColumn::make('position')->summarize(Sum::make())])
            ->groups(['region'])
            ->paginated([10]),
    );

    $host->tableGrouping = 'region';

    $crawler = new Crawler(renderToHtml($host->getTable()));
    $summaries = $crawler->filter('tr.sy-ta-summary-row')->each(static fn (Crawler $row): string => mb_trim($row->filter('td')->last()->text()));

    // Alpha 2 and Charlie 3 under north, Bravo 1 under south; the band closes
    // each group and then the relation as a whole, and each cell carries the
    // summarizer's own label beside its figure.
    expect($crawler->filter('tr.sy-ta-group-header-row')->count())->toBe(2)
        ->and(compositeOwnerTableRenderedKeys($crawler))->toBe(['north~four', 'north~two', 'south~two'])
        ->and($summaries)->toBe(['Sum5', 'Sum1', 'Sum6']);
});

it('numbers a submitted order of the scoped relation 1..N and leaves every other record alone', function (): void {
    $entityManager = compositeOwnerTableRig();

    $host = compositeOwnerTableHost(
        $entityManager,
        compositeOwnerTableCircuit($entityManager, 'north', 'one'),
        'children',
        static fn (Table $table): Table => compositeOwnerTableCircuitColumns($table)->reorderable('position'),
    );

    $host->reorderTable(['south~two', 'north~four', 'north~two']);
    $host->reorderTable(['west~four']);

    $entityManager->clear();

    $positions = [];

    foreach ($entityManager->getRepository(Circuit::class)->findBy([], ['name' => 'ASC']) as $circuit) {
        $positions[$circuit->getName()] = $circuit->getPosition();
    }

    // 'west~four' is the sibling owner's child, so the second call writes nothing.
    expect($positions)->toBe([
        'Alpha' => 3,
        'Bravo' => 1,
        'Charlie' => 2,
        'Delta' => 4,
        'Loose' => 7,
        'Root' => 5,
        'Sibling' => 9,
    ]);
});

/*
 * The owner subselect's `sy_owner` and `sy_related` live in the query's one flat
 * identification-variable namespace but reached the builder inside a raw `WHERE`
 * string, so `getAllAliases()` cannot see them. A dotted column whose first hop
 * is a property of that name is all it takes to mint the same alias again — no
 * user code, and the commonest name a mapped-by field has.
 */
it('joins a hop named after an alias the owner subselect already holds', function (): void {
    $entityManager = compositeOwnerTableRig();
    $root = compositeOwnerTableCircuit($entityManager, 'north', 'one');

    $sorted = compositeOwnerTableHost($entityManager, $root, 'children', compositeOwnerTableCircuitColumns(...));
    $sorted->tableSort = 'related.name:asc';

    $summarized = compositeOwnerTableHost(
        $entityManager,
        $root,
        'children',
        static fn (Table $table): Table => $table->columns([
            TextColumn::make('name'),
            TextColumn::make('owner.position')->summarize(Sum::make()),
        ]),
    );

    $crawler = new Crawler(renderToHtml($summarized->getTable()));

    // Alpha points at Loose, Charlie at Root and Bravo at Sibling, so the hop is
    // what orders them — and at the base the same read is a semantical error
    // rather than a wrong row.
    expect(compositeOwnerTableKeys($sorted))->toBe(['north~two', 'north~four', 'south~two'])
        ->and($sorted->getSelectedTableRecordsQuery()->getDQL())->toContain('LEFT JOIN record.owner sy_owner_2 LEFT JOIN record.related sy_related_2')
        // Three children of one owner, each summing the owner's own position.
        ->and(mb_trim($crawler->filter('tr.sy-ta-summary-row')->last()->filter('td')->last()->text()))->toBe('Sum15');
});

/*
 * A characterization, not a refusal of ours: this is the target axis, and it
 * reads the same on a `query()` table. A column, group or filter naming a to-one
 * association without a dotted field behind it compiles to a bare path, which
 * Doctrine refuses against a class keyed by several fields.
 */
it('refuses a dotless association path against a target keyed by several fields, as Doctrine does', function (): void {
    $entityManager = compositeOwnerTableRig();
    $root = compositeOwnerTableCircuit($entityManager, 'north', 'one');

    $grouped = compositeOwnerTableHost($entityManager, $root, 'children', static fn (Table $table): Table => $table->columns([TextColumn::make('name')])->groups(['owner']));
    $grouped->tableGrouping = 'owner';

    $sorted = compositeOwnerTableHost($entityManager, $root, 'children', static fn (Table $table): Table => $table->columns([TextColumn::make('name'), TextColumn::make('owner')->sortable()]));
    $sorted->tableSort = 'owner:asc';

    $message = 'A single-valued association path expression to an entity with a composite primary key is not supported.';

    expect(static fn (): array => $grouped->getTable()->getRecords())->toThrow(QueryException::class, $message)
        ->and(static fn (): array => $sorted->getTable()->getRecords())->toThrow(QueryException::class, $message);
});

it('mounts a record action of a scalar-pair owner relation by key, writes an editable cell and links each row', function (): void {
    [$client, $entityManager] = compositeOwnerTableSeededClient();

    $component = $this->createLiveComponent('CompositeOwnerRelationshipTableNotes', client: $client);
    $crawler = new Crawler((string) $component->render());

    $component
        ->call('mountAction', ['name' => 'promote', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => '2']])
        ->call('callMountedAction')
        ->call('updateTableColumnState', ['column' => 'heading', 'record' => '1', 'input' => 'Beta the second']);

    $entityManager->clear();

    $headings = array_map(
        static fn (ShortcutNote $note): string => $note->getHeading(),
        $entityManager->getRepository(ShortcutNote::class)->findBy([], ['id' => 'ASC']),
    );

    expect(compositeOwnerTableRenderedKeys($crawler))->toBe(['2', '1'])
        ->and($crawler->filter('tbody tr[id*=".table.records."] a[href^="/notes/"]')->each(static fn (Crawler $link): string => (string) $link->attr('href')))
        ->toContain('/notes/2')
        // Note 3 belongs to the sibling owner and note 4 to nobody; neither is
        // reachable from this table, so neither may move.
        ->and($headings)->toBe(['Beta the second', 'Alpha (promoted)', 'Gamma', 'Delta']);
});

it('deletes exactly the selected records of the scoped relation without fetching them', function (): void {
    [$client, $entityManager] = compositeOwnerTableSeededClient();

    $this->createLiveComponent('CompositeOwnerRelationshipTableNotes', client: $client)
        ->set('selectedTableRecords', ['1', '3'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    $entityManager->clear();

    $survivors = array_map(
        static fn (ShortcutNote $note): int => $note->getId(),
        $entityManager->getRepository(ShortcutNote::class)->findBy([], ['id' => 'ASC']),
    );

    // '3' is the sibling owner's note: selected in the bag, dropped by the
    // relation the selection query is scoped to.
    expect($survivors)->toBe([2, 3, 4]);
});

it('lists and acts on the relation of an owner whose first identifier half is an association', function (): void {
    [$client, $entityManager] = compositeOwnerTableSeededClient();

    expect(compositeOwnerTableRenderedKeys(new Crawler((string) $this->createLiveComponent('CompositeOwnerRelationshipTableRiders', client: $client)->render())))->toBe(['1'])
        ->and(compositeOwnerTableRenderedKeys(new Crawler((string) $this->createLiveComponent('CompositeOwnerRelationshipTableRiders', ['ownerSeason' => 'winter-2027'], client: $client)->render())))->toBe(['2']);

    $component = $this->createLiveComponent('CompositeOwnerRelationshipTableRiders', client: $client)
        ->call('mountAction', ['name' => 'promote', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => '1']]);

    // A warm identity map answers the key out of memory, which hides how the
    // relation layer resolves an owner whose first identifier half is an
    // association.
    $entityManager->clear();

    $component->call('callMountedAction');

    $entityManager->clear();

    expect(array_map(
        static fn (ResidencyRider $rider): string => $rider->getName(),
        $entityManager->getRepository(ResidencyRider::class)->findBy([], ['id' => 'ASC']),
    ))->toBe(['Lights (promoted)', 'Rigging']);
});
