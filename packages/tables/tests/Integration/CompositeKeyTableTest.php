<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

uses(InteractsWithLiveComponents::class);

/*
 * A composite key's whole round trip through a real live host, both flavours:
 * the scalar pair `Shortcut` (host, path) and the derived identity `Residency`
 * (venue, season) over the `Handle`-keyed `Venue`. The keys are written as
 * literals rather than computed, because the literal is the pin — a codec that
 * changed its escaping would otherwise agree with itself and pass.
 *
 * The `Shortcut` at ('sylament.test', '/docs/a b') is deliberately hostile: its
 * key carries `=2F` for the slash the URL layer cannot represent, `=2E` for the
 * dot every state path splits on, and `=20` for the space a DOM id cannot hold.
 *
 * Rows come back in identifier order — `host asc, path asc` and
 * `venue asc, season asc` — never in seed order: neither host declares a sort,
 * so the identifier tiebreak is the only ordering there is, and
 * `reorderable()` contributes none until a drag turns `isTableReordering()` on.
 * Every expected list below is written in that order on purpose.
 *
 * Kernel reboot is disabled throughout for `DeleteBulkActionTest`'s reason: the
 * fixture database is an in-memory SQLite held by a container service, so a
 * reboot between two live requests would hand the second an empty one.
 */

function shortcutRecord(string $host, string $path, string $label, int $position, bool $active): Shortcut
{
    $shortcut = new Shortcut();
    $shortcut->setHost($host);
    $shortcut->setPath($path);
    $shortcut->setLabel($label);
    $shortcut->setPosition($position);
    $shortcut->setActive($active);

    return $shortcut;
}

function residencyRecord(Venue $venue, string $season, string $label, int $position, bool $active): Residency
{
    $residency = new Residency();
    $residency->setVenue($venue);
    $residency->setSeason($season);
    $residency->setLabel($label);
    $residency->setPosition($position);
    $residency->setActive($active);

    return $residency;
}

/**
 * @return array{TestLiveComponent, EntityManagerInterface}
 */
function compositeShortcutsRig(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    EntityManagerFactory::seed(
        $entityManager,
        shortcutRecord('sylament.test', '/docs/a b', 'Docs', 1, true),
        shortcutRecord('example.org', 'home', 'Home', 2, false),
        shortcutRecord('example.org', 'about', 'About', 3, true),
    );

    return [test()->createLiveComponent('CompositeShortcutsTable', client: $client), $entityManager];
}

/**
 * Both venues and all three residencies go through one `seed()` call: a derived
 * identity needs its owner persisted in the same flush, and `seed()` clears
 * afterwards, so a second call would hand the residencies a detached `Venue`.
 *
 * @return array{TestLiveComponent, EntityManagerInterface}
 */
function compositeResidenciesRig(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $north = new Venue(new Handle('north.hall'), 'North Hall');
    $astra = new Venue(new Handle('astra-hall'), 'Astra Hall');

    EntityManagerFactory::seed(
        $entityManager,
        $north,
        $astra,
        residencyRecord($north, 'summer-2026', 'Summer', 1, true),
        residencyRecord($astra, 'winter-2026', 'Winter', 2, false),
        residencyRecord($astra, 'spring-2027', 'Spring', 3, true),
    );

    return [test()->createLiveComponent('CompositeResidenciesTable', client: $client), $entityManager];
}

function reloadedShortcut(EntityManagerInterface $entityManager, string $host, string $path): Shortcut
{
    $entityManager->clear();

    $shortcut = $entityManager->find(Shortcut::class, ['host' => $host, 'path' => $path]);

    assert($shortcut instanceof Shortcut);

    return $shortcut;
}

/**
 * @return array<string, int>
 */
function storedCompositeShortcutPositions(EntityManagerInterface $entityManager): array
{
    $entityManager->clear();

    $positions = [];

    foreach ($entityManager->getRepository(Shortcut::class)->findBy([], ['position' => 'ASC']) as $shortcut) {
        $positions[$shortcut->getHost() . '|' . $shortcut->getPath()] = $shortcut->getPosition();
    }

    return $positions;
}

it('keys every row id and every checkbox by the encoded composite key', function (): void {
    [$component] = compositeShortcutsRig();

    $crawler = new Crawler((string) $component->render());
    $ids = $crawler->filter('tr.sy-ta-row')->each(static fn (Crawler $row): string => (string) $row->attr('id'));
    $values = $crawler->filter('input.sy-ta-record-checkbox')->each(static fn (Crawler $box): string => (string) $box->attr('value'));

    expect($values)->toBe(['example=2Eorg~about', 'example=2Eorg~home', 'sylament=2Etest~=2Fdocs=2Fa=20b'])
        ->and($ids)->toHaveCount(3);

    foreach ($values as $index => $value) {
        expect($ids[$index])->toEndWith('.table.records.' . $value);
    }
});

it('keys a derived-identity row through its target entity', function (): void {
    [$component] = compositeResidenciesRig();

    $values = new Crawler((string) $component->render())
        ->filter('input.sy-ta-record-checkbox')
        ->each(static fn (Crawler $box): string => (string) $box->attr('value'));

    expect($values)->toBe(['astra-hall~spring-2027', 'astra-hall~winter-2026', 'north=2Ehall~summer-2026']);
});

it('carries the encoded composite key in a rendered row action mount descriptor', function (): void {
    [$component] = compositeShortcutsRig();

    $row = new Crawler((string) $component->render())
        ->filter('tr.sy-ta-row')
        ->reduce(static fn (Crawler $row): bool => $row->filter('input.sy-ta-record-checkbox[value="sylament=2Etest~=2Fdocs=2Fa=20b"]')->count() === 1);

    expect($row)->toHaveCount(1);

    $trigger = $row->filter('.sy-ta-actions .sy-ac')->first();

    $context = json_decode((string) $trigger->attr('data-live-context-param'), true, flags: JSON_THROW_ON_ERROR);

    expect($trigger->attr('data-live-action-param'))->toBe('mountAction')
        ->and($trigger->attr('data-live-name-param'))->toBe('promote')
        ->and($context)->toBe(['recordKey' => 'sylament=2Etest~=2Fdocs=2Fa=20b', 'table' => true]);
});

it('mounts a row action with its composite record and writes through it', function (): void {
    [$component, $entityManager] = compositeShortcutsRig();

    $component
        ->call('mountAction', ['name' => 'promote', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => 'sylament=2Etest~=2Fdocs=2Fa=20b']])
        ->call('callMountedAction');

    expect(reloadedShortcut($entityManager, 'sylament.test', '/docs/a b')->getLabel())->toBe('Docs (promoted)')
        ->and(reloadedShortcut($entityManager, 'example.org', 'home')->getLabel())->toBe('Home');
});

it('unmounts a row action whose composite key is not canonical', function (): void {
    [$component] = compositeShortcutsRig();

    $mounted = $component
        ->call('mountAction', ['name' => 'promote', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => 'sylament.test~/docs/a b']])
        ->component();

    expect($mounted->mountedActions)->toBe([]);
});

it('writes an editable cell through the encoded composite key', function (): void {
    [$component, $entityManager] = compositeShortcutsRig();

    $html = (string) $component
        ->call('updateTableColumnState', ['column' => 'active', 'record' => 'example=2Eorg~home', 'input' => true])
        ->render();

    expect(reloadedShortcut($entityManager, 'example.org', 'home')->isActive())->toBeTrue()
        ->and($html)->toContain('data-sylament--tables--editable-cell-record-value="example=2Eorg~home"');
});

it('writes nothing for an editable-cell key that is not canonical', function (): void {
    [$component, $entityManager] = compositeShortcutsRig();

    $component->call('updateTableColumnState', ['column' => 'active', 'record' => 'example.org~home', 'input' => true]);

    expect(reloadedShortcut($entityManager, 'example.org', 'home')->isActive())->toBeFalse();
});

it('writes a derived-identity cell through the key its target minted', function (): void {
    [$component, $entityManager] = compositeResidenciesRig();

    $component->call('updateTableColumnState', ['column' => 'active', 'record' => 'astra-hall~winter-2026', 'input' => true]);

    $entityManager->clear();

    $residency = $entityManager->getRepository(Residency::class)->findOneBy(['season' => 'winter-2026']);

    expect($residency?->isActive())->toBeTrue();
});

it('deletes exactly the selected composite rows without fetching them', function (): void {
    [$component, $entityManager] = compositeShortcutsRig();

    $component
        ->set('selectedTableRecords', ['sylament=2Etest~=2Fdocs=2Fa=20b', 'example=2Eorg~about'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    $entityManager->clear();

    $survivors = array_map(
        static fn (Shortcut $shortcut): string => $shortcut->getHost() . '|' . $shortcut->getPath(),
        $entityManager->getRepository(Shortcut::class)->findAll(),
    );

    expect($survivors)->toBe(['example.org|home']);
});

it('deletes exactly the selected derived-identity rows without fetching them', function (): void {
    [$component, $entityManager] = compositeResidenciesRig();

    $component
        ->set('selectedTableRecords', ['north=2Ehall~summer-2026', 'astra-hall~spring-2027'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    $entityManager->clear();

    $survivors = array_map(
        static fn (Residency $residency): string => $residency->getSeason(),
        $entityManager->getRepository(Residency::class)->findAll(),
    );

    expect($survivors)->toBe(['winter-2026']);
});

it('numbers a submitted composite order 1..N', function (): void {
    [$component, $entityManager] = compositeShortcutsRig();

    $component->call('reorderTable', ['order' => [
        'example=2Eorg~about',
        'sylament=2Etest~=2Fdocs=2Fa=20b',
        'example=2Eorg~home',
    ]]);

    expect(storedCompositeShortcutPositions($entityManager))->toBe([
        'example.org|about' => 1,
        'sylament.test|/docs/a b' => 2,
        'example.org|home' => 3,
    ]);
});

it('leaves a gap where a reorder key that is not canonical sat', function (): void {
    [$component, $entityManager] = compositeShortcutsRig();

    $component->call('reorderTable', ['order' => [
        'example=2Eorg~about',
        'example.org~home',
        'sylament=2Etest~=2Fdocs=2Fa=20b',
    ]]);

    expect(storedCompositeShortcutPositions($entityManager))->toBe([
        'example.org|about' => 1,
        'example.org|home' => 2,
        'sylament.test|/docs/a b' => 3,
    ]);
});
