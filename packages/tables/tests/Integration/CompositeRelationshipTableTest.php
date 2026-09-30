<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\ShortcutHit;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * A relation-scoped table whose target is keyed by more than an `id`: the
 * owning and the inverse arm of a many-to-many onto the scalar-pair
 * `Shortcut`, and a third arm onto the derived-identity `Residency` over the
 * `Handle`-keyed `Venue`. The owner is one artist, single-identifier
 * throughout.
 *
 * The rows of the second artist exist in every case so that "lists the owner's
 * records" can fail: the relationship predicate is a correlated `EXISTS` over
 * the owner's own association, and a subselect that lost its correlation would
 * list every record of the target class instead.
 *
 * Keys are written as literals, never minted through the codec in an
 * expectation — a codec agreeing with itself proves nothing. `Almanac` sorts
 * before `Zenith` while its key sorts after, so the mapping's
 * `#[ORM\OrderBy(['label' => SortDirection::Ascending])]` is visible in every unsorted list.
 *
 * Kernel reboot is disabled throughout for `CompositeKeyTableTest`'s reason:
 * the fixture database is an in-memory SQLite held by a container service, so
 * a reboot between two live requests would hand the second an empty one.
 */

/**
 * Two artists over four shortcuts, two residencies and four hits. Ada (1)
 * holds the two hostile-keyed shortcuts and the north-hall residency; Bob (2)
 * holds the spare shortcut and the astra residency, and bills Ada's `Zenith`,
 * while Ada is billed the spare one — so the owning and the inverse arm of the
 * same pair of classes answer different sets.
 *
 * `Beacon` belongs to nobody and shares `Zenith`'s host, so a correlation
 * written on the first identifier field alone lists it under Ada.
 *
 * The map is cleared once the seed has flushed, the way
 * `EntityManagerFactory::seed()` clears it: only the owning side of each
 * association is written here, so a warm map would hand every read the empty
 * in-memory collections these objects were constructed with rather than the
 * rows the flush wrote.
 *
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function compositeRelationTableSeededClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $ada = new Artist(1, 'Ada');
    $bob = new Artist(2, 'Bob');

    $shortcuts = [];

    foreach ([['plain', 'about', 'Zenith'], ['www.example.com', '/blog/2026/a b', 'Almanac'], ['spare', 'unattached', 'Marquee'], ['plain', 'contact', 'Beacon']] as [$host, $path, $label]) {
        $shortcut = new Shortcut();
        $shortcut->setHost($host);
        $shortcut->setPath($path);
        $shortcut->setLabel($label);
        $shortcuts[] = $shortcut;
    }

    [$zenith, $almanac, $marquee] = $shortcuts;

    $north = new Venue(new Handle('north.hall'), 'North Hall');
    $astra = new Venue(new Handle('astra-hall'), 'Astra Hall');

    $summer = new Residency();
    $summer->setVenue($north);
    $summer->setSeason('summer-2026');
    $summer->setLabel('Summer');

    $winter = new Residency();
    $winter->setVenue($astra);
    $winter->setSeason('winter-2026');
    $winter->setLabel('Winter');

    $hits = [
        new ShortcutHit(1, 'curl/8.7', 5, $zenith),
        new ShortcutHit(2, 'Mozilla/5.0', 9, $zenith),
        new ShortcutHit(3, 'curl/8.7', 3, $almanac),
        new ShortcutHit(4, 'lynx/2.9', 1, $marquee),
    ];

    foreach ([$ada, $bob, ...$shortcuts, $north, $astra, $summer, $winter, ...$hits] as $entity) {
        $entityManager->persist($entity);
    }

    $ada->addShortcut($zenith)->addShortcut($almanac)->addResidency($summer);
    $bob->addShortcut($marquee)->addResidency($winter);
    $marquee->addTroupe($ada);
    $zenith->addTroupe($bob);

    $entityManager->flush();
    $entityManager->clear();

    return [$client, $entityManager];
}

/**
 * The record keys one host lists, straight off the keyed record array.
 *
 * @param array<string, mixed> $props
 *
 * @return list<string>
 */
function compositeRelationTableKeys(string $component, KernelBrowser $client, array $props = []): array
{
    return array_map(
        static fn (int|string $key): string => (string) $key,
        array_keys(test()->createLiveComponent($component, $props, client: $client)->component()->getTable()->getRecords()),
    );
}

/**
 * The record rows' keys as the markup carries them, read off the one attribute
 * every record row has — never `.sy-ta-row`, which a summary row wears too.
 *
 * @return list<string>
 */
function compositeRelationTableRenderedKeys(Crawler $crawler): array
{
    return $crawler->filter('tbody tr[id*=".table.records."]')->each(
        static fn (Crawler $row): string => mb_substr((string) $row->attr('id'), mb_strpos((string) $row->attr('id'), '.table.records.') + mb_strlen('.table.records.')),
    );
}

/**
 * Every row's aggregate count cell, in row order, read by the column's own cell
 * id — a selection column and an actions column flank the columns, so no
 * position from either end names it.
 *
 * @return list<string>
 */
function compositeRelationTableCounts(Crawler $crawler): array
{
    return $crawler->filter('tbody tr[id*=".table.records."]')->each(
        static fn (Crawler $row): string => mb_trim($row->filter('td[id$=".column.hits_count"]')->text()),
    );
}

/**
 * Every row's bulleted hit agents, in row order.
 *
 * @return list<list<string>>
 */
function compositeRelationTableAgents(Crawler $crawler): array
{
    return $crawler->filter('tbody tr[id*=".table.records."]')->each(
        static fn (Crawler $row): array => $row->filter('.sy-bulleted li')->each(static fn (Crawler $item): string => mb_trim($item->text())),
    );
}

/**
 * The labels a host's selection resolves to, in the order the selection query
 * answers them.
 *
 * @param list<object|array<string, mixed>>|list<string> $records
 *
 * @return list<string>
 */
function compositeRelationTableLabels(array $records): array
{
    return array_values(array_map(
        static fn (object|array|string $record): string => $record instanceof Shortcut ? $record->getLabel() : '',
        $records,
    ));
}

it('lists the records of one owner and none of another, keyed by the target identifier', function (): void {
    [$client] = compositeRelationTableSeededClient();

    $component = $this->createLiveComponent('ArtistShortcutsTable', client: $client);

    expect(compositeRelationTableKeys('ArtistShortcutsTable', $client))->toBe(['www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b', 'plain~about'])
        ->and(compositeRelationTableKeys('ArtistShortcutsTable', $client, ['artistId' => 2]))->toBe(['spare~unattached'])
        ->and($component->component()->getTable()->getRecordClass())->toBe(Shortcut::class)
        ->and($component->component()->getTableRecord('spare~unattached'))->toBeNull()
        ->and($component->component()->getTableRecord('plain~about'))->toBeInstanceOf(Shortcut::class);
});

it('scopes the base query by a correlated subselect over the owner association', function (): void {
    [$client] = compositeRelationTableSeededClient();

    $shortcuts = (string) $this->createLiveComponent('ArtistShortcutsTable', client: $client)->component()->getTable()->getRelationshipQuery()?->getDQL();
    $billed = (string) $this->createLiveComponent('ArtistBilledShortcutsTable', client: $client)->component()->getTable()->getRelationshipQuery()?->getDQL();
    $residencies = (string) $this->createLiveComponent('ArtistResidenciesTable', client: $client)->component()->getTable()->getRelationshipQuery()?->getDQL();

    // The leading `WHERE ` is part of every expectation: `toContain('EXISTS')`
    // alone passes against the negated predicate too.
    expect($shortcuts)->toContain('WHERE EXISTS (SELECT 1 FROM ' . Artist::class . ' sy_owner JOIN sy_owner.shortcuts sy_related WHERE sy_owner.id = :sylament_relationship_owner AND sy_related.host = record.host AND sy_related.path = record.path)')
        ->and($billed)->toContain('WHERE EXISTS (SELECT 1 FROM ' . Artist::class . ' sy_owner JOIN sy_owner.billedShortcuts sy_related WHERE sy_owner.id = :sylament_relationship_owner AND sy_related.host = record.host AND sy_related.path = record.path)')
        ->and($residencies)->toContain('WHERE EXISTS (SELECT 1 FROM ' . Artist::class . ' sy_owner JOIN sy_owner.residencies sy_related WHERE sy_owner.id = :sylament_relationship_owner AND sy_related.venue = record.venue AND sy_related.season = record.season)');
});

it('takes its default order from the mapping until a header sort wins', function (): void {
    [$client] = compositeRelationTableSeededClient();

    $sorted = $this->createLiveComponent('ArtistShortcutsTable', client: $client)
        ->call('sortTable', ['column' => 'hits_count', 'direction' => 'desc'])
        ->component();

    expect(compositeRelationTableKeys('ArtistShortcutsTable', $client))->toBe(['www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b', 'plain~about'])
        ->and(array_keys($sorted->getTable()->getRecords()))->toBe(['plain~about', 'www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b']);
});

it('keys every rendered row and checkbox of the relation by the encoded identifier', function (): void {
    [$client] = compositeRelationTableSeededClient();

    $crawler = new Crawler((string) $this->createLiveComponent('ArtistShortcutsTable', client: $client)->render());
    $values = $crawler->filter('input.sy-ta-record-checkbox')->each(static fn (Crawler $box): string => (string) $box->attr('value'));

    expect(compositeRelationTableRenderedKeys($crawler))->toBe(['www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b', 'plain~about'])
        ->and($values)->toBe(['www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b', 'plain~about']);
});

it('mounts a row action of the relation by key and writes through it', function (): void {
    [$client, $entityManager] = compositeRelationTableSeededClient();

    $this->createLiveComponent('ArtistShortcutsTable', client: $client)
        ->call('mountAction', ['name' => 'promote', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => 'www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b']])
        ->call('callMountedAction');

    $entityManager->clear();

    expect($entityManager->find(Shortcut::class, ['host' => 'www.example.com', 'path' => '/blog/2026/a b'])?->getLabel())->toBe('Almanac (promoted)')
        ->and($entityManager->find(Shortcut::class, ['host' => 'plain', 'path' => 'about'])?->getLabel())->toBe('Zenith');
});

it('resolves a selection of the relation in both selection strategies', function (): void {
    [$client] = compositeRelationTableSeededClient();

    $selected = $this->createLiveComponent('ArtistShortcutsTable', client: $client)
        ->set('selectedTableRecords', ['plain~about', 'spare~unattached'])
        ->component();

    $tracking = $this->createLiveComponent('ArtistShortcutsTable', client: $client)
        ->set('isTrackingDeselectedTableRecords', true)
        ->set('deselectedTableRecords', ['plain~about'])
        ->component();

    // 'spare~unattached' is the other artist's row: a well-formed key of the
    // target class that this owner's relation never offered.
    expect(compositeRelationTableLabels($selected->getSelectedTableRecords()))->toBe(['Zenith'])
        ->and(compositeRelationTableLabels($tracking->getSelectedTableRecords()))->toBe(['Almanac'])
        // The enumeration plucks its keys off the filtered query, which carries
        // no ordering of its own, so it answers a set rather than the rendered
        // order.
        ->and($tracking->getAllSelectableTableRecordKeys())->toEqualCanonicalizing(['plain~about', 'www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b'])
        ->and($tracking->getAllSelectableTableRecordsCount())->toBe(2);
});

it('pages the relation through its identifiers when the query joins a collection', function (): void {
    [$client] = compositeRelationTableSeededClient();

    $component = $this->createLiveComponent('ArtistShortcutsTable', ['joinsHits' => true], client: $client);
    $first = new Crawler((string) $component->render());
    $second = new Crawler((string) $component->call('gotoTablePage', ['page' => 2])->render());

    expect(compositeRelationTableRenderedKeys($first))->toBe(['www=2Eexample=2Ecom~=2Fblog=2F2026=2Fa=20b'])
        ->and(compositeRelationTableAgents($first))->toBe([['curl/8.7']])
        ->and(compositeRelationTableRenderedKeys($second))->toBe(['plain~about'])
        ->and(compositeRelationTableAgents($second))->toBe([['curl/8.7', 'Mozilla/5.0']])
        ->and(mb_trim($second->filter('.sy-pagination-overview')->first()->text()))->toBe('Showing 2 to 2 of 2 results');
});

it('counts and searches a relation of the related records without leaving the owner', function (): void {
    [$client] = compositeRelationTableSeededClient();

    $listed = new Crawler((string) $this->createLiveComponent('ArtistShortcutsTable', client: $client)->render());
    $searched = new Crawler((string) $this->createLiveComponent('ArtistShortcutsTable', client: $client)->set('tableSearch', 'Mozilla')->render());

    // 'lynx/2.9' is a hit of the other artist's shortcut, so the searchable hop
    // may not reach it from this owner's table.
    $foreign = new Crawler((string) $this->createLiveComponent('ArtistShortcutsTable', client: $client)->set('tableSearch', 'lynx')->render());

    expect(compositeRelationTableCounts($listed))->toBe(['1', '2'])
        ->and(compositeRelationTableRenderedKeys($searched))->toBe(['plain~about'])
        ->and(compositeRelationTableRenderedKeys($foreign))->toBe([]);
});

it('lists the same composite target from the inverse side of the many-to-many', function (): void {
    [$client] = compositeRelationTableSeededClient();

    expect(compositeRelationTableKeys('ArtistBilledShortcutsTable', $client))->toBe(['spare~unattached'])
        ->and(compositeRelationTableKeys('ArtistBilledShortcutsTable', $client, ['artistId' => 2]))->toBe(['plain~about']);
});

it('lists a derived-identity target and acts on one from a cold identity map', function (): void {
    [$client, $entityManager] = compositeRelationTableSeededClient();

    expect(compositeRelationTableKeys('ArtistResidenciesTable', $client))->toBe(['north=2Ehall~summer-2026'])
        ->and(compositeRelationTableKeys('ArtistResidenciesTable', $client, ['artistId' => 2]))->toBe(['astra-hall~winter-2026']);

    $component = $this->createLiveComponent('ArtistResidenciesTable', client: $client)
        ->call('mountAction', ['name' => 'promote', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => 'north=2Ehall~summer-2026']]);

    // A warm identity map answers the key out of memory, which hides how the
    // relation layer resolves an identifier whose first half is an association.
    $entityManager->clear();

    $component->call('callMountedAction');

    $entityManager->clear();

    expect($entityManager->getRepository(Residency::class)->findOneBy(['season' => 'summer-2026'])?->getLabel())->toBe('Summer (promoted)')
        ->and($entityManager->getRepository(Residency::class)->findOneBy(['season' => 'winter-2026'])?->getLabel())->toBe('Winter');
});

/*
 * The by-key arm issues one DQL `DELETE`, so a row it takes must be free to go:
 * `artist_shortcut` cascades on the shortcut side, and the row deleted here has
 * no hit and no troupe, only the join row that makes it part of the relation.
 */
it('deletes exactly the selected related rows of the relation without fetching them', function (): void {
    [$client, $entityManager] = compositeRelationTableSeededClient();

    $nadir = new Shortcut();
    $nadir->setHost('plain');
    $nadir->setPath('faq');
    $nadir->setLabel('Nadir');

    $entityManager->persist($nadir);
    $entityManager->find(Artist::class, 1)?->addShortcut($nadir);
    $entityManager->flush();
    $entityManager->clear();

    $this->createLiveComponent('ArtistShortcutsTable', client: $client)
        ->set('selectedTableRecords', ['plain~faq', 'spare~unattached'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    $entityManager->clear();

    $survivors = array_map(
        static fn (Shortcut $shortcut): string => $shortcut->getLabel(),
        $entityManager->getRepository(Shortcut::class)->findBy([], ['label' => 'ASC']),
    );

    // 'spare~unattached' is the other artist's row: selected in the bag, dropped
    // by the relation the selection query is scoped to.
    expect($survivors)->toBe(['Almanac', 'Beacon', 'Marquee', 'Zenith']);
});

/*
 * A selected row that something still references without a cascade: `Zenith` has
 * two hits. The one `DELETE` fails whole, nothing is deleted, and the danger toast
 * says so, on every platform the suite runs.
 */
it('deletes nothing and says so when a selected related row is still referenced', function (): void {
    [$client, $entityManager] = compositeRelationTableSeededClient();

    $component = $this->createLiveComponent('ArtistShortcutsTable', client: $client)
        ->set('selectedTableRecords', ['plain~about'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    $toast = (string) ($component->getDispatchedBrowserEvent($component->render(), 'sylament:notification')['payload']['html'] ?? '');

    $entityManager->clear();

    expect($entityManager->getRepository(Shortcut::class)->count([]))->toBe(4)
        ->and($toast)->toContain('Failed to delete 1 records');
});
