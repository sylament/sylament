<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\ResidencyShow;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\ShortcutHit;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;
use Sylament\Tables\Tests\Fixtures\Doctrine\RecordingMiddleware;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

uses(InteractsWithLiveComponents::class);

/*
 * The depths of this epic meeting on one live host, which no unit of them can
 * reach alone: a keyed root fetch-joining a collection, sorted by a correlated
 * relationship count, narrowed by a relation search, paged, and acted on.
 *
 * A matched record hydrates its collection whole — the search filters roots
 * through an `EXISTS`, never the joined rows — and every expected list below
 * is written in that light.
 *
 * Kernel reboot is disabled for `CompositeKeyTableTest`'s reason: the fixture
 * database is an in-memory SQLite held by a container service, so a reboot
 * between two live requests would hand the second an empty one.
 */

function depthResidency(Venue $venue, string $season, string $label, int $position): Residency
{
    $residency = new Residency();
    $residency->setVenue($venue);
    $residency->setSeason($season);
    $residency->setLabel($label);
    $residency->setPosition($position);

    return $residency;
}

function depthShortcut(string $host, string $path, string $label, int $position): Shortcut
{
    $shortcut = new Shortcut();
    $shortcut->setHost($host);
    $shortcut->setPath($path);
    $shortcut->setLabel($label);
    $shortcut->setPosition($position);

    return $shortcut;
}

/**
 * Five residencies over two venues, with show counts 3/2/1/1/0 that run against
 * both the seed order and the identifier order, so a count-sorted page can be
 * mistaken for neither. Only the first three own a show titled "… night", and
 * only the Astra ones have a venue name a search for "astra" reaches, so the
 * two searchable hops narrow to different sets. Positions run 1–5 in seed
 * order, which the reorder case rewrites.
 *
 * The three-show residency is the page-slicing pin: with two records to a page
 * its shows alone would fill a window over the joined rows.
 *
 * @return array{TestLiveComponent, EntityManagerInterface}
 */
function joinedResidenciesRig(bool $paginatesSimply = false): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $north = new Venue(new Handle('north.hall'), 'North Hall');
    $astra = new Venue(new Handle('astra-hall'), 'Astra Hall');

    $summer = depthResidency($north, 'summer-2026', 'Summer', 1);
    $winter = depthResidency($astra, 'winter-2026', 'Winter', 2);
    $spring = depthResidency($astra, 'spring-2027', 'Spring', 3);
    $encore = depthResidency($north, 'winter-2027', 'Encore', 4);

    EntityManagerFactory::seed(
        $entityManager,
        $north,
        $astra,
        $summer,
        $winter,
        $spring,
        $encore,
        depthResidency($north, 'autumn-2026', 'Autumn', 5),
        new ResidencyShow(1, 'Opening night', 120, $summer),
        new ResidencyShow(2, 'Closing night', 140, $summer),
        new ResidencyShow(3, 'Matinee', 30, $summer),
        new ResidencyShow(4, 'Winter night', 80, $winter),
        new ResidencyShow(5, 'Rehearsal', 10, $winter),
        new ResidencyShow(6, 'Spring night', 60, $spring),
        new ResidencyShow(7, 'Quiet day', 20, $encore),
    );

    return [test()->createLiveComponent('CompositeJoinedResidenciesTable', ['paginatesSimply' => $paginatesSimply], client: $client), $entityManager];
}

/**
 * Four scalar-pair shortcuts at positions 10/20/30/40, three of them hit by
 * curl — so a search narrows the set, the page row sums the two records on the
 * page (30) and the all-table row sums the three the search left (60).
 *
 * @return array{TestLiveComponent, EntityManagerInterface}
 */
function summarizedShortcutsRig(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $docs = depthShortcut('sylament.test', '/docs/a b', 'Docs', 10);
    $home = depthShortcut('example.org', 'home', 'Home', 20);
    $about = depthShortcut('example.org', 'about', 'About', 30);

    EntityManagerFactory::seed(
        $entityManager,
        $docs,
        $home,
        $about,
        depthShortcut('example.org', 'legal', 'Legal', 40),
        new ShortcutHit(1, 'curl/8.7', 5, $docs),
        new ShortcutHit(2, 'curl/8.7', 3, $home),
        new ShortcutHit(3, 'Mozilla/5.0', 9, $about),
        new ShortcutHit(4, 'curl/8.7', 1, $about),
    );

    return [test()->createLiveComponent('CompositeSummarizedShortcutsTable', client: $client), $entityManager];
}

/**
 * The record rows' keys, read off the one attribute every row carries — never
 * `.sy-ta-row`, which a summary row wears too.
 *
 * @return list<string>
 */
function depthRowKeys(Crawler $crawler): array
{
    return $crawler->filter('tbody tr[id*=".table.records."]')->each(
        static fn (Crawler $row): string => mb_substr((string) $row->attr('id'), mb_strpos((string) $row->attr('id'), '.table.records.') + mb_strlen('.table.records.')),
    );
}

/**
 * Every row's bulleted show titles, in row order.
 *
 * @return list<list<string>>
 */
function depthShowTitles(Crawler $crawler): array
{
    return $crawler->filter('tbody tr[id*=".table.records."]')->each(
        static fn (Crawler $row): array => $row->filter('.sy-bulleted li')->each(static fn (Crawler $item): string => mb_trim($item->text())),
    );
}

/**
 * Every row's aggregate count cell, in row order, read by the column's own
 * cell id — a selection column and an actions column flank the columns, so no
 * position from either end names it.
 *
 * @return list<string>
 */
function depthCounts(Crawler $crawler): array
{
    return $crawler->filter('tbody tr[id*=".table.records."]')->each(
        static fn (Crawler $row): string => mb_trim($row->filter('td[id$=".column.shows_count"]')->text()),
    );
}

function depthOverview(Crawler $crawler): string
{
    return mb_trim($crawler->filter('.sy-pagination-overview')->first()->text());
}

it('pages a searched, count-sorted fetch join over a derived-identity root', function (): void {
    [$component] = joinedResidenciesRig();

    $crawler = new Crawler((string) $component
        ->set('tableSearch', 'night')
        ->call('sortTable', ['column' => 'shows_count', 'direction' => 'desc'])
        ->render());

    expect(depthRowKeys($crawler))->toBe(['north=2Ehall~summer-2026', 'astra-hall~winter-2026'])
        ->and(depthShowTitles($crawler))->toBe([
            ['Opening night', 'Closing night', 'Matinee'],
            ['Winter night', 'Rehearsal'],
        ])
        ->and(depthCounts($crawler))->toBe(['3', '2'])
        ->and(depthOverview($crawler))->toBe('Showing 1 to 2 of 3 results');
});

it('reads the second page of the same query rather than the first over again', function (): void {
    [$component] = joinedResidenciesRig();

    $crawler = new Crawler((string) $component
        ->set('tableSearch', 'night')
        ->call('sortTable', ['column' => 'shows_count', 'direction' => 'desc'])
        ->call('gotoTablePage', ['page' => 2])
        ->render());

    expect(depthRowKeys($crawler))->toBe(['astra-hall~spring-2027'])
        ->and(depthShowTitles($crawler))->toBe([['Spring night']])
        ->and(depthOverview($crawler))->toBe('Showing 3 to 3 of 3 results');
});

/*
 * The hop here *is* half the identifier, so the subselect joins the very
 * association it correlates back on — bare paths, one per identifier field,
 * beside a projection this root wraps in `IDENTITY()` for every search it
 * carries, that field being the first of its identifier.
 */
it('narrows the joined page by a search across the to-one hop that is an identifier field', function (): void {
    [$component] = joinedResidenciesRig();

    $crawler = new Crawler((string) $component
        ->set('tableSearch', 'astra')
        ->call('sortTable', ['column' => 'shows_count', 'direction' => 'desc'])
        ->render());

    expect(depthRowKeys($crawler))->toBe(['astra-hall~winter-2026', 'astra-hall~spring-2027'])
        ->and(depthShowTitles($crawler))->toBe([['Winter night', 'Rehearsal'], ['Spring night']])
        ->and(depthOverview($crawler))->toBe('Showing 1 to 2 of 2 results');
});

it('summarizes a searched page of a scalar-pair root against the whole searched set', function (): void {
    [$component] = summarizedShortcutsRig();

    $crawler = new Crawler((string) $component->set('tableSearch', 'curl')->render());
    $rows = $crawler->filter('tr.sy-ta-summary-row');

    expect(depthRowKeys($crawler))->toBe(['sylament=2Etest~=2Fdocs=2Fa=20b', 'example=2Eorg~home'])
        ->and($rows->each(static fn (Crawler $row): string => mb_trim($row->filter('th[scope="row"]')->text())))
        ->toBe(['This page', 'All shortcuts'])
        ->and($rows->first()->filter('td')->last()->text())->toContain('30')
        ->and($rows->last()->filter('td')->last()->text())->toContain('60');
});

it('reads a simple-mode page of the same joined query with a look-ahead and no total', function (): void {
    [$component] = joinedResidenciesRig(paginatesSimply: true);

    $crawler = new Crawler((string) $component
        ->set('tableSearch', 'night')
        ->call('sortTable', ['column' => 'shows_count', 'direction' => 'desc'])
        ->render());

    expect(depthRowKeys($crawler))->toBe(['north=2Ehall~summer-2026', 'astra-hall~winter-2026'])
        ->and(depthShowTitles($crawler))->toBe([
            ['Opening night', 'Closing night', 'Matinee'],
            ['Winter night', 'Rehearsal'],
        ])
        ->and(depthOverview($crawler))->toBe('Showing 1 to 2 results')
        ->and($crawler->filter('.sy-pagination-item[rel="next"]'))->toHaveCount(1);
});

it('mounts a row action on a fetch-joined keyed row and writes through it', function (): void {
    [$component, $entityManager] = joinedResidenciesRig();

    $component
        ->set('tableSearch', 'night')
        ->call('sortTable', ['column' => 'shows_count', 'direction' => 'desc'])
        ->call('mountAction', ['name' => 'promote', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => 'north=2Ehall~summer-2026']])
        ->call('callMountedAction');

    $entityManager->clear();

    expect($entityManager->getRepository(Residency::class)->findOneBy(['season' => 'summer-2026'])?->getLabel())->toBe('Summer (promoted)')
        ->and($entityManager->getRepository(Residency::class)->findOneBy(['season' => 'winter-2026'])?->getLabel())->toBe('Winter');
});

/*
 * Select-all plucks its keys off the joined query, whose rows a to-many join
 * multiplies — six for the three searched residencies, five once the
 * deselected one is taken out — so the single `DELETE` carrying exactly two
 * key pairs is what says the pluck deduplicated.
 */
it('deletes the selected rows of a searched joined page without fetching them', function (): void {
    [$component, $entityManager] = joinedResidenciesRig();

    $recorder = test()->getContainer()->get(RecordingMiddleware::class);
    $recorder->reset();

    $component
        ->set('tableSearch', 'night')
        ->call('sortTable', ['column' => 'shows_count', 'direction' => 'desc'])
        ->set('isTrackingDeselectedTableRecords', true)
        ->set('deselectedTableRecords', ['astra-hall~spring-2027'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    $deletes = array_values(array_filter($recorder->statements(), static fn (string $sql): bool => str_starts_with($sql, 'DELETE')));

    $entityManager->clear();

    $survivors = array_map(
        static fn (Residency $residency): string => $residency->getSeason(),
        $entityManager->getRepository(Residency::class)->findBy([], ['season' => 'ASC']),
    );

    expect($survivors)->toBe(['autumn-2026', 'spring-2027', 'winter-2027'])
        ->and($deletes)->toHaveCount(1)
        ->and(mb_substr_count($deletes[0], 'venue_id = ?'))->toBe(2);
});

it('numbers a submitted order of the searched joined page 1..N', function (): void {
    [$component, $entityManager] = joinedResidenciesRig();

    $component
        ->set('tableSearch', 'night')
        ->call('sortTable', ['column' => 'shows_count', 'direction' => 'desc'])
        ->call('reorderTable', ['order' => [
            'astra-hall~winter-2026',
            'astra-hall~spring-2027',
            'north=2Ehall~summer-2026',
        ]]);

    $entityManager->clear();

    $positions = [];

    foreach ($entityManager->getRepository(Residency::class)->findBy([], ['season' => 'ASC']) as $residency) {
        $positions[$residency->getSeason()] = $residency->getPosition();
    }

    expect($positions)->toBe([
        'autumn-2026' => 5,
        'spring-2027' => 2,
        'summer-2026' => 3,
        'winter-2026' => 1,
        'winter-2027' => 4,
    ]);
});
