<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Colophon;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\ColophonLine;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\StampMark;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\ColophonLineResource;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateColophonLine;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateStampMark;
use Sylament\Panels\Tests\Fixtures\Resources\StampMarkResource;
use Sylament\Panels\Tests\Fixtures\Resources\StampResource;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Kernel reboot is disabled for the same reason `NestedResourcePagesTest`
 * disables it: the fixture database lives in memory on a container service, so
 * a reboot between two requests would hand the second one a freshly-migrated,
 * empty one.
 *
 * Every key below is a literal: catalogue "First" is `1` and the stamp whose
 * code reads `A/B C` under it is `1~A=2FB=20C`. Minting an expected value
 * through the codec would make a case pass against any format at all.
 */

/**
 * The two parent flavours a single-scalar identifier cannot express, in one
 * database: a stamp under a catalogue (the derived identity), a colophon of
 * the same catalogue (the single derived identity) and a child under each.
 *
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function compositeGuardParentClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $catalogue = new Catalogue()->setTitle('First');
    $stamp = new Stamp()->setCatalogue($catalogue)->setCode('A/B C')->setCaption('Alpha');
    $colophon = new Colophon()->setCatalogue($catalogue)->setNote('Note');

    EntityManagerFactory::seed(
        $entityManager,
        $catalogue,
        $stamp,
        new StampMark()->setStamp($stamp)->setLabel('Mark'),
        $colophon,
        new ColophonLine()->setColophon($colophon)->setText('Line'),
    );

    return [$client, $entityManager];
}

/**
 * A second catalogue carrying a stamp and a colophon of its own, each with one
 * child — the rows a scope that reads only half the parent's identifier, or
 * none of it, would let through.
 */
function compositeGuardRivalParents(EntityManagerInterface $entityManager): void
{
    $catalogue = new Catalogue()->setTitle('Second');
    $stamp = new Stamp()->setCatalogue($catalogue)->setCode('A/B C')->setCaption('Beta');
    $colophon = new Colophon()->setCatalogue($catalogue)->setNote('Other note');

    EntityManagerFactory::seed(
        $entityManager,
        $catalogue,
        $stamp,
        new StampMark()->setStamp($stamp)->setLabel('Rival mark'),
        $colophon,
        new ColophonLine()->setColophon($colophon)->setText('Rival line'),
    );
}

/**
 * @param class-string<Resource> $class
 */
function compositeGuardResource(string $class): Resource
{
    $registry = test()->getContainer()->get(ResourceRegistry::class);

    expect($registry)->toBeInstanceOf(ResourceRegistry::class);

    return $registry->get($class);
}

/**
 * The scoped children of one parent record, read back as the one field each
 * child carries.
 *
 * @param class-string<Resource> $class
 *
 * @return list<string>
 */
function compositeGuardScopedTexts(string $class, object $parentRecord, string $getter): array
{
    $records = compositeGuardResource($class)->getParentScopedRecordQuery($parentRecord)->getQuery()->getResult();

    expect($records)->toBeArray();

    return array_map(static fn (object $record): string => (string) $record->{$getter}(), $records);
}

/**
 * The stamp foreign key a mark's row really holds, read as columns rather than
 * through the mapping — the code column stores what the record upper-cases.
 *
 * @return array<string, mixed>
 */
function compositeGuardStoredStampColumns(EntityManagerInterface $entityManager, string $label): array
{
    $row = $entityManager->getConnection()->fetchAssociative(
        'SELECT stamp_catalogue_id, stamp_code FROM StampMark WHERE label = ?',
        [$label],
    );

    expect($row)->toBeArray();

    return is_array($row) ? $row : [];
}

it('scopes a nested resource to a derived-identity parent record', function (): void {
    [, $entityManager] = compositeGuardParentClient();
    compositeGuardRivalParents($entityManager);

    $alpha = $entityManager->getRepository(Stamp::class)->findOneBy(['caption' => 'Alpha']);
    $beta = $entityManager->getRepository(Stamp::class)->findOneBy(['caption' => 'Beta']);

    expect($alpha)->toBeInstanceOf(Stamp::class)
        ->and($beta)->toBeInstanceOf(Stamp::class);

    $query = compositeGuardResource(StampMarkResource::class)->getParentScopedRecordQuery($alpha);

    // The two stamps differ in nothing but their catalogue, so a scope binding
    // the code alone would answer both rows for either parent. The outer
    // builder keeps its one root: the owner is reached inside a correlated
    // subselect rather than through a join a later sort could collide with.
    expect($query->getDQL())->toBe('SELECT record FROM Sylament\Panels\Tests\Fixtures\Doctrine\Entity\StampMark record WHERE EXISTS (SELECT 1 FROM Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp sy_owner JOIN sy_owner.marks sy_related WHERE sy_owner.catalogue = :sylament_relationship_owner_0 AND sy_owner.code = :sylament_relationship_owner_1 AND sy_related.id = record.id)')
        ->and($query->getAllAliases())->toBe(['record'])
        ->and(compositeGuardScopedTexts(StampMarkResource::class, $alpha, 'getLabel'))->toBe(['Mark'])
        ->and(compositeGuardScopedTexts(StampMarkResource::class, $beta, 'getLabel'))->toBe(['Rival mark']);
});

it('scopes a nested resource to a single-derived-identity parent record', function (): void {
    // `Colophon` is keyed by one association and nothing else, which Doctrine
    // reports as *not* composite — so this is the arm that keeps the plain
    // foreign-key comparison, and its parameter keeps the unsuffixed name.
    [, $entityManager] = compositeGuardParentClient();
    compositeGuardRivalParents($entityManager);

    $colophon = $entityManager->getRepository(Colophon::class)->findOneBy(['note' => 'Note']);
    $rival = $entityManager->getRepository(Colophon::class)->findOneBy(['note' => 'Other note']);

    expect($colophon)->toBeInstanceOf(Colophon::class)
        ->and($rival)->toBeInstanceOf(Colophon::class)
        ->and($entityManager->getClassMetadata(Colophon::class)->isIdentifierComposite)->toBeFalse();

    $query = compositeGuardResource(ColophonLineResource::class)->getParentScopedRecordQuery($colophon);

    expect($query->getDQL())->toBe('SELECT record FROM Sylament\Panels\Tests\Fixtures\Doctrine\Entity\ColophonLine record WHERE record.colophon = :sylament_relationship_owner')
        ->and(compositeGuardScopedTexts(ColophonLineResource::class, $colophon, 'getText'))->toBe(['Line'])
        ->and(compositeGuardScopedTexts(ColophonLineResource::class, $rival, 'getText'))->toBe(['Rival line']);
});

it('serves a nested page under a derived-identity parent with the child filled in', function (): void {
    [$client] = compositeGuardParentClient();

    $crawler = $client->request('GET', '/key/catalogues/1/stamps/1~A=2FB=20C/stamp-marks/1/edit');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('input[data-model$="data.label"]')->attr('value'))->toBe('Mark');
});

it('serves a nested page under a single-derived-identity parent the same way', function (): void {
    [$client] = compositeGuardParentClient();

    $crawler = $client->request('GET', '/key/colophons/1/colophon-lines/1/edit');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('input[data-model$="data.text"]')->attr('value'))->toBe('Line');
});

it('attaches a record to a derived-identity parent record', function (): void {
    [, $entityManager] = compositeGuardParentClient();

    $stamp = $entityManager->getRepository(Stamp::class)->findOneBy(['caption' => 'Alpha']);

    expect($stamp)->toBeInstanceOf(Stamp::class);

    $mark = new StampMark()->setLabel('Attached');

    compositeGuardResource(StampMarkResource::class)->attachRecordToParent($mark, $stamp);

    $entityManager->persist($mark);
    $entityManager->flush();
    $entityManager->clear();

    // Both halves of the parent's identifier reach the row, and the code half
    // travels as the column stores it rather than as the record reads it.
    expect(compositeGuardStoredStampColumns($entityManager, 'Attached'))
        ->toBe(['stamp_catalogue_id' => 1, 'stamp_code' => 'a/b c']);
});

it('attaches a record to a single-derived-identity parent record', function (): void {
    [, $entityManager] = compositeGuardParentClient();

    $colophon = $entityManager->getRepository(Colophon::class)->findOneBy(['note' => 'Note']);

    expect($colophon)->toBeInstanceOf(Colophon::class);

    $line = new ColophonLine()->setText('Attached');

    compositeGuardResource(ColophonLineResource::class)->attachRecordToParent($line, $colophon);

    $entityManager->persist($line);
    $entityManager->flush();
    $entityManager->clear();

    $stored = $entityManager->getRepository(ColophonLine::class)->findOneBy(['text' => 'Attached']);

    expect($stored)->toBeInstanceOf(ColophonLine::class)
        ->and($stored?->getColophon()?->getCatalogue()?->getId())->toBe(1);
});

it('characterizes: a create page under a parent of either flavour the guards refused renders without scoping a query through it', function (string $url): void {
    // A create page resolves the parent record but scopes no query through it,
    // so this one rendered while the write it submits was still refused — it
    // passes unchanged either way, and is kept as the boundary between the two.
    [$client] = compositeGuardParentClient();

    $client->request('GET', $url);

    expect($client->getResponse()->getStatusCode())->toBe(200);
})->with([
    'a derived-identity parent' => ['/key/catalogues/1/stamps/1~A=2FB=20C/stamp-marks/create'],
    'a single-derived-identity parent' => ['/key/colophons/1/colophon-lines/create'],
]);

it('writes the row that create page submits under the parent it was opened from', function (string $pageClass, array $parentRecords, array $data, string $recordClass): void {
    [$client, $entityManager] = compositeGuardParentClient();
    compositeGuardRivalParents($entityManager);

    $before = $entityManager->getRepository($recordClass)->count();

    $component = $this->createLiveComponent($pageClass, [
        'parentRecords' => $parentRecords,
        'panelId' => 'key',
    ], client: $client);

    $component->set('data', $data);
    $component->call('create');

    $entityManager->clear();

    expect($entityManager->getRepository($recordClass)->count())->toBe($before + 1);
})->with([
    'a derived-identity parent' => [
        CreateStampMark::class,
        ['catalogue' => '1', 'stamp' => '1~A=2FB=20C'],
        ['label' => 'Written'],
        StampMark::class,
    ],
    'a single-derived-identity parent' => [
        CreateColophonLine::class,
        ['colophon' => '1'],
        ['text' => 'Written'],
        ColophonLine::class,
    ],
]);

it('stores both halves of a composite parent key on the row the create page writes', function (): void {
    [$client, $entityManager] = compositeGuardParentClient();
    compositeGuardRivalParents($entityManager);

    $component = $this->createLiveComponent(CreateStampMark::class, [
        'parentRecords' => ['catalogue' => '2', 'stamp' => '2~A=2FB=20C'],
        'panelId' => 'key',
    ], client: $client);

    $component->set('data', ['label' => 'Under the rival']);
    $component->call('create');

    $entityManager->clear();

    // The two catalogues hold a stamp of the same code, so a row written with
    // the code alone is indistinguishable from one written under the other
    // parent — the stored pair is what tells them apart.
    expect(compositeGuardStoredStampColumns($entityManager, 'Under the rival'))
        ->toBe(['stamp_catalogue_id' => 2, 'stamp_code' => 'a/b c']);
});

it('scopes a nested resource to a single-identifier parent record', function (): void {
    [, $entityManager] = compositeGuardParentClient();

    $catalogue = $entityManager->getRepository(Catalogue::class)->findOneBy(['title' => 'First']);
    $other = new Catalogue()->setTitle('Second');

    EntityManagerFactory::seed($entityManager, $other);

    expect($catalogue)->toBeInstanceOf(Catalogue::class);

    $resource = compositeGuardResource(StampResource::class);
    $scoped = $resource->getParentScopedRecordQuery($catalogue)->getQuery()->getResult();
    $foreign = $resource->getParentScopedRecordQuery($other)->getQuery()->getResult();

    expect($scoped)->toHaveCount(1)
        ->and($scoped[0])->toBeInstanceOf(Stamp::class)
        ->and($scoped[0]->getCaption())->toBe('Alpha')
        ->and($foreign)->toHaveCount(0);
});
