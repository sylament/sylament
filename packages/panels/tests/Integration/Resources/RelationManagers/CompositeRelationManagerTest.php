<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditCatalogue;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ManageCatalogueStamps;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\PlainStampsRelationManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Kernel reboot is disabled throughout: the fixture database lives in memory on
 * a container service, so a reboot between two requests would hand the second
 * one a freshly-migrated, empty one.
 *
 * Both hosts of the relationship tier are driven here over one association —
 * `Catalogue::$stamps`, a single-identifier owner holding records of a derived
 * identity whose identifier association *is* the relationship. The embedded
 * manager is read through a page `GET` rather than a hand mount: a manager
 * carries no panel of its own, so a live request to one resolves the default
 * panel and every related-resource URL it mints names a route no panel
 * registered.
 */

/**
 * Three stamps over two catalogues, so a scoping miss shows up as a third row,
 * and the second catalogue's stamp is the foreign key a scoped action refuses.
 *
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function compositeRelationClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $first = new Catalogue()->setTitle('First');
    $second = new Catalogue()->setTitle('Second');

    EntityManagerFactory::seed(
        $entityManager,
        $first,
        $second,
        new Stamp()->setCatalogue($first)->setCode('A/B C')->setCaption('Alpha'),
        new Stamp()->setCatalogue($first)->setCode('en-gb')->setCaption('Beta'),
        new Stamp()->setCatalogue($second)->setCode('ZZ')->setCaption('Foreign'),
    );

    return [$client, $entityManager];
}

/**
 * A row's DOM id is the table host id, the `table.records` segment and the
 * record key — this is the key half alone.
 *
 * @return array<int, string>
 */
function compositeRelationRowKeys(Crawler $rows): array
{
    return $rows->each(static fn (Crawler $row): string => explode('.table.records.', (string) $row->attr('id'), 2)[1] ?? '');
}

/**
 * @return array<int, string>
 */
function compositeRelationStampCodes(EntityManagerInterface $entityManager): array
{
    $entityManager->clear();

    $codes = array_map(
        static fn (Stamp $stamp): string => $stamp->getCode() . '@' . $stamp->getCatalogue()?->getId(),
        $entityManager->getRepository(Stamp::class)->findAll(),
    );

    sort($codes);

    return $codes;
}

it('lists the owner records a composite one-to-many holds, keyed by the whole identifier', function (): void {
    [$client] = compositeRelationClient();

    $crawler = $client->request('GET', '/key/catalogues/1/edit');
    $rows = $crawler->filter('section.sy-pa-relation-manager tbody .sy-ta-row');
    $rowText = implode(' ', $rows->each(fn (Crawler $row): string => $row->text()));

    // `EN-GB` is stored lower case and read back upper, so a key minted off the
    // column rather than the record would be `1~en-gb` here; the third stamp
    // belongs to the other catalogue and is the scoping miss.
    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('section.sy-pa-relation-manager')->count())->toBe(1)
        ->and($rows->count())->toBe(2)
        ->and(compositeRelationRowKeys($rows))->toBe(['1~A=2FB=20C', '1~EN-GB'])
        ->and($rowText)->toContain('A/B C')
        ->and($rowText)->toContain('EN-GB')
        ->and($rowText)->not->toContain('Foreign');
});

it('mounts the embedded manager with the owner route key and the embedding page class', function (): void {
    [$client] = compositeRelationClient();

    $crawler = $client->request('GET', '/key/catalogues/1/edit');
    $props = json_decode((string) $crawler->filter('section.sy-pa-relation-manager')->attr('data-live-props-value'), true);

    // The owner rides the embed as its route key, never as the record — the
    // wire carries a scalar, and the page class is what the manager resolves
    // that key back through.
    expect($props['ownerRecord'])->toBe('1')
        ->and($props['pageClass'])->toBe(EditCatalogue::class);
});

it('links every row of the embedded manager at the related resource under its parent', function (): void {
    [$client] = compositeRelationClient();

    $crawler = $client->request('GET', '/key/catalogues/1/edit');
    $hrefs = array_values(array_unique($crawler->filter('section.sy-pa-relation-manager a[href]')->each(fn (Crawler $link): ?string => $link->attr('href'))));

    // Both halves of a nested URL at once: the parent segment is the owner's
    // key and the record segment is the composite child's, escaped and with no
    // percent-encoding anywhere in it.
    expect($hrefs)->toBe([
        'http://localhost/key/catalogues/1/stamps/create',
        'http://localhost/key/catalogues/1/stamps/1~A=2FB=20C/edit',
        'http://localhost/key/catalogues/1/stamps/1~EN-GB/edit',
    ]);
});

it('serves the manage page over the same association, keyed and linked the same way', function (): void {
    [$client] = compositeRelationClient();

    $crawler = $client->request('GET', '/key/catalogues/1/stamps');
    $rows = $crawler->filter('tbody .sy-ta-row');

    // The second host of the relationship tier: the page keys its own record —
    // the owner — into the root class token, and the table keys the composite
    // records it lists.
    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('.sy-pa-page.sy-pa-resource-manage-related-records-page.sy-pa-resource-record-1')->count())->toBe(1)
        ->and($rows->count())->toBe(2)
        ->and(compositeRelationRowKeys($rows))->toBe(['1~A=2FB=20C', '1~EN-GB'])
        ->and($crawler->filter('.sy-ta-header-actions a')->attr('href'))->toBe('http://localhost/key/catalogues/1/stamps/create')
        ->and($crawler->filter('tbody .sy-ta-row a.sy-ta-col')->first()->attr('href'))->toBe('http://localhost/key/catalogues/1/stamps/1~A=2FB=20C/edit');
});

it('deletes exactly the composite record a table-context action names', function (): void {
    [$client, $entityManager] = compositeRelationClient();

    $component = $this->createLiveComponent(ManageCatalogueStamps::class, ['record' => '1', 'panelId' => 'key'], client: $client)
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => '1~EN-GB']])
        ->call('callMountedAction');

    expect($component->component()->mountedActions)->toBe([])
        ->and(compositeRelationStampCodes($entityManager))->toBe(['A/B C@1', 'ZZ@2']);
});

it('refuses a table-context action naming a composite record of another owner', function (): void {
    [$client, $entityManager] = compositeRelationClient();

    $component = $this->createLiveComponent(ManageCatalogueStamps::class, ['record' => '1', 'panelId' => 'key'], client: $client)
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => '2~ZZ']])
        ->call('callMountedAction');

    // The descriptor's key carries no checksum, so the relationship-scoped
    // table is what refuses it: the frame never resolves, so it unmounts
    // unopened and the other catalogue's stamp is still there.
    expect($component->component()->mountedActions)->toBe([])
        ->and(compositeRelationStampCodes($entityManager))->toBe(['A/B C@1', 'EN-GB@1', 'ZZ@2']);
});

it('creates a composite record from the relationship create modal and attaches it to the owner', function (): void {
    [$client, $entityManager] = compositeRelationClient();

    $component = $this->createLiveComponent(ManageCatalogueStamps::class, ['record' => '1', 'panelId' => 'key'], client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => ['table' => true]])
        ->set('mountedActionsData.0.code', 'NEW/1')
        ->set('mountedActionsData.0.caption', 'Modal')
        ->call('callMountedAction');

    // The owner is half the new record's identifier, so the attach has to
    // happen before the record is persisted — Doctrine refuses to generate an
    // identifier whose association still reads unset.
    expect($component->component()->mountedActions)->toBe([])
        ->and(compositeRelationStampCodes($entityManager))->toBe(['A/B C@1', 'EN-GB@1', 'NEW/1@1', 'ZZ@2']);
});

it('deletes a composite record from a relation manager mounted on its own', function (): void {
    [$client, $entityManager] = compositeRelationClient();

    $component = $this->createLiveComponent(PlainStampsRelationManager::class, ['ownerRecord' => '1', 'pageClass' => EditCatalogue::class], client: $client)
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => '1~A=2FB=20C']])
        ->call('callMountedAction');

    expect($component->component()->mountedActions)->toBe([])
        ->and(compositeRelationStampCodes($entityManager))->toBe(['EN-GB@1', 'ZZ@2']);
});

it('creates a composite record from the create modal of a relation manager mounted on its own', function (): void {
    [$client, $entityManager] = compositeRelationClient();

    $component = $this->createLiveComponent(PlainStampsRelationManager::class, ['ownerRecord' => '1', 'pageClass' => EditCatalogue::class], client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => ['table' => true]])
        ->set('mountedActionsData.0.code', 'NEW/2')
        ->set('mountedActionsData.0.caption', 'Manager')
        ->call('callMountedAction');

    expect($component->component()->mountedActions)->toBe([])
        ->and(compositeRelationStampCodes($entityManager))->toBe(['A/B C@1', 'EN-GB@1', 'NEW/2@1', 'ZZ@2']);
});
