<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Forms\Components\Select;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Gallery;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateGalleryImprint;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditGallery;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\GalleryImprintsRelationManager;
use Sylament\Testing\Doctrine\RecordingMiddleware;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The relationship tier with the composite record on the far side: a
 * single-identifier owner, a many-to-many, and a target keyed by two string
 * columns that the key grammar escapes. Everything the manager does to that
 * association — list, offer, attach, detach, bulk detach — travels as a record
 * key, so this file is where the reader's correlated `EXISTS` and the actions'
 * by-key writes are read through a panel rather than a bare table host.
 *
 * Kernel reboot is disabled throughout: the fixture database lives in memory on
 * a container service, so a reboot between two requests would hand the second
 * one a freshly-migrated, empty one.
 */

/**
 * Two galleries over three imprints, so the join table holds rows for both and
 * a scoping miss shows up as a third row. `Exotic` hangs off the second
 * gallery alone, which makes it the one candidate the first gallery lacks.
 *
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function compositeTargetManagerClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $escaped = new Imprint()->setHouse('www.example.com')->setYear('/blog 2026')->setLabel('Escaped');
    $plain = new Imprint()->setHouse('plain')->setYear('about')->setLabel('Plain');
    $exotic = new Imprint()->setHouse('a~b')->setYear('c=d ž')->setLabel('Exotic');

    $main = new Gallery()->setName('Main')->addImprint($escaped)->addImprint($plain);
    $other = new Gallery()->setName('Other')->addImprint($exotic);

    EntityManagerFactory::seed($entityManager, $escaped, $plain, $exotic, $main, $other);

    return [$client, $entityManager];
}

/**
 * A row's DOM id is the table host id, the `table.records` segment and the
 * record key — this is the key half alone.
 *
 * @return array<int, string>
 */
function compositeTargetManagerRowKeys(Crawler $rows): array
{
    return $rows->each(static fn (Crawler $row): string => explode('.table.records.', (string) $row->attr('id'), 2)[1] ?? '');
}

/**
 * The labels one gallery holds, read back from the database rather than from
 * the collection the write touched.
 *
 * @return list<string>
 */
function compositeTargetManagerLabelsOf(EntityManagerInterface $entityManager, int $galleryId): array
{
    $entityManager->clear();

    $gallery = $entityManager->find(Gallery::class, $galleryId) ?? throw new LogicException('The gallery vanished.');

    return array_map(static fn (Imprint $imprint): string => $imprint->getLabel(), $gallery->getImprints()->toArray());
}

/**
 * The container-wide statement recorder, emptied, so what a case reads off it
 * afterwards is what ran since.
 */
function compositeTargetManagerRecorder(): RecordingMiddleware
{
    $recorder = test()->getContainer()->get(RecordingMiddleware::class);
    assert($recorder instanceof RecordingMiddleware);

    $recorder->reset();

    return $recorder;
}

function compositeTargetManagerRecordSelect(GalleryImprintsRelationManager $manager): Select
{
    $select = $manager->getMountedActionSchema()?->getComponent('recordId');

    return $select instanceof Select ? $select : throw new LogicException('The attach modal carries no record select.');
}

it('lists the composite records a many-to-many holds, keyed by the whole identifier', function (): void {
    [$client] = compositeTargetManagerClient();

    $crawler = $client->request('GET', '/key/galleries/1/edit');
    $rows = $crawler->filter('section.sy-pa-relation-manager tbody .sy-ta-row');
    $rowText = implode(' ', $rows->each(fn (Crawler $row): string => $row->text()));

    // Both identifier fields carry a character the grammar escapes, and the
    // mapping's `#[OrderBy]` on the label is what fixes the row order; the
    // third imprint hangs off the other gallery and is the scoping miss.
    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('section.sy-pa-relation-manager')->count())->toBe(1)
        ->and($rows->count())->toBe(2)
        ->and(compositeTargetManagerRowKeys($rows))->toBe(['www=2Eexample=2Ecom~=2Fblog=202026', 'plain~about'])
        ->and($rowText)->toContain('www.example.com')
        ->and($rowText)->toContain('/blog 2026')
        ->and($rowText)->not->toContain('Exotic');
});

it('offers the composite records the owner lacks, keyed by the record key', function (): void {
    [$client, $entityManager] = compositeTargetManagerClient();

    $manager = $this->createLiveComponent(GalleryImprintsRelationManager::class, ['ownerRecord' => '1', 'pageClass' => EditGallery::class], client: $client)
        ->call('mountAction', ['name' => 'attach', 'arguments' => [], 'context' => ['table' => true]])
        ->component();

    $select = compositeTargetManagerRecordSelect($manager);
    $entityManager->clear();

    // The candidate query answers the one imprint this gallery does not hold,
    // under a key the codec minted from both identifier columns — and the
    // empty identity map is what says the option list was projected rather
    // than hydrated.
    expect($select->getOptions())->toBe(['a=7Eb~c=3Dd=20=C5=BE' => 'Exotic'])
        ->and($entityManager->getUnitOfWork()->tryGetById(['house' => 'a~b', 'year' => 'c=d ž'], Imprint::class))->toBeFalse();
});

it('attaches the composite record the select was given, leaving the other owner alone', function (): void {
    [$client, $entityManager] = compositeTargetManagerClient();

    $component = $this->createLiveComponent(GalleryImprintsRelationManager::class, ['ownerRecord' => '1', 'pageClass' => EditGallery::class], client: $client)
        ->call('mountAction', ['name' => 'attach', 'arguments' => [], 'context' => ['table' => true]])
        ->set('mountedActionsData.0.recordId', 'a=7Eb~c=3Dd=20=C5=BE')
        ->call('callMountedAction');

    expect($component->component()->mountedActions)->toBe([])
        ->and(compositeTargetManagerLabelsOf($entityManager, 1))->toBe(['Escaped', 'Exotic', 'Plain'])
        ->and(compositeTargetManagerLabelsOf($entityManager, 2))->toBe(['Exotic']);
});

it('detaches exactly the composite record a table-context action names', function (): void {
    [$client, $entityManager] = compositeTargetManagerClient();

    $component = $this->createLiveComponent(GalleryImprintsRelationManager::class, ['ownerRecord' => '1', 'pageClass' => EditGallery::class], client: $client)
        ->call('mountAction', ['name' => 'detach', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => 'plain~about']])
        ->call('callMountedAction');

    expect($component->component()->mountedActions)->toBe([])
        ->and(compositeTargetManagerLabelsOf($entityManager, 1))->toBe(['Escaped']);
});

it('detaches a whole selection of composite records and clears it', function (): void {
    [$client, $entityManager] = compositeTargetManagerClient();

    $component = $this->createLiveComponent(GalleryImprintsRelationManager::class, ['ownerRecord' => '1', 'pageClass' => EditGallery::class], client: $client)
        ->set('selectedTableRecords', ['www=2Eexample=2Ecom~=2Fblog=202026', 'plain~about'])
        ->call('mountAction', ['name' => 'detach', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    expect($component->component()->mountedActions)->toBe([])
        ->and($component->component()->selectedTableRecordKeys)->toBe([])
        ->and(compositeTargetManagerLabelsOf($entityManager, 1))->toBe([])
        ->and(compositeTargetManagerLabelsOf($entityManager, 2))->toBe(['Exotic']);
});

it('refuses a table-context action naming a composite record of another owner', function (): void {
    [$client, $entityManager] = compositeTargetManagerClient();

    $component = $this->createLiveComponent(GalleryImprintsRelationManager::class, ['ownerRecord' => '1', 'pageClass' => EditGallery::class], client: $client)
        ->call('mountAction', ['name' => 'detach', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => 'a=7Eb~c=3Dd=20=C5=BE']])
        ->call('callMountedAction');

    // The key carries no checksum, so the relationship-scoped table is what
    // refuses it: the frame never resolves, so it unmounts unopened and the
    // other gallery keeps its imprint.
    expect($component->component()->mountedActions)->toBe([])
        ->and(compositeTargetManagerLabelsOf($entityManager, 1))->toBe(['Escaped', 'Plain'])
        ->and(compositeTargetManagerLabelsOf($entityManager, 2))->toBe(['Exotic']);
});

it('refuses to attach a composite key the candidate query never offered', function (): void {
    [$client, $entityManager] = compositeTargetManagerClient();

    $manager = $this->createLiveComponent(GalleryImprintsRelationManager::class, ['ownerRecord' => '1', 'pageClass' => EditGallery::class], client: $client)
        ->call('mountAction', ['name' => 'attach', 'arguments' => [], 'context' => ['table' => true]])
        ->component();

    $schema = $manager->getMountedActionSchema();
    $action = $manager->getMountedAction() ?? throw new LogicException('Nothing mounted.');
    $action->data(['recordId' => 'plain~about']);

    // Already attached, so the candidate query answers nothing for it — the
    // same gate a malformed key meets, since neither resolves a record.
    expect(fn (): mixed => $action->call(['schema' => $schema, 'form' => $schema]))
        ->toThrow(LogicException::class, 'its select never offered')
        ->and(compositeTargetManagerLabelsOf($entityManager, 1))->toBe(['Escaped', 'Plain']);
});

it('narrows a malformed composite key to an impossible predicate rather than binding half of it', function (): void {
    [$client, $entityManager] = compositeTargetManagerClient();

    $manager = $this->createLiveComponent(GalleryImprintsRelationManager::class, ['ownerRecord' => '1', 'pageClass' => EditGallery::class], client: $client)
        ->call('mountAction', ['name' => 'attach', 'arguments' => [], 'context' => ['table' => true]])
        ->component();

    $schema = $manager->getMountedActionSchema();
    $action = $manager->getMountedAction() ?? throw new LogicException('Nothing mounted.');
    $action->data(['recordId' => 'a=7Eb~c=3Dd=20=C5=BE~extra']);

    $recorder = compositeTargetManagerRecorder();

    expect(fn (): mixed => $action->call(['schema' => $schema, 'form' => $schema]))
        ->toThrow(LogicException::class, 'its select never offered');

    $statements = $recorder->statements();

    // Three fields' worth of key for a two-field class. It is narrowed to an
    // impossible predicate rather than bound a half at a time, so no part of
    // the malformed text reaches a parameter and the refusal is the count
    // mismatch behind it.
    expect($statements)->toHaveCount(1)
        ->and($statements[0])->toEndWith('AND 1 = 0')
        ->and($statements[0])->not->toContain('extra')
        ->and(compositeTargetManagerLabelsOf($entityManager, 1))->toBe(['Escaped', 'Plain']);
});

it('excludes the records the owner already holds through a correlated exists', function (): void {
    [$client] = compositeTargetManagerClient();

    $manager = $this->createLiveComponent(GalleryImprintsRelationManager::class, ['ownerRecord' => '1', 'pageClass' => EditGallery::class], client: $client)
        ->call('mountAction', ['name' => 'attach', 'arguments' => [], 'context' => ['table' => true]])
        ->component();

    $recorder = compositeTargetManagerRecorder();
    compositeTargetManagerRecordSelect($manager)->getOptions();

    $statements = $recorder->statements();

    // Both identifier columns are correlated, which is the whole point: an
    // `IN` over the first column alone would exclude a record sharing a house
    // with an attached one, and the owner is one bound parameter either way.
    expect($statements)->toHaveCount(1)
        ->and($statements[0])->toContain('NOT EXISTS')
        ->and($statements[0])->toContain('i2_.house = i0_.house AND i2_.year = i0_.year');
});

it('serves a composite record nested under a many-to-many parent', function (): void {
    [$client] = compositeTargetManagerClient();

    $crawler = $client->request('GET', '/key/galleries/1/gallery-imprints/plain~about/edit');

    // The parent scope is a many-to-many here rather than the one-to-many
    // every nested vertical before it used, and the record is keyed by a pair.
    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('.sy-pa-page')->attr('class'))->toContain('sy-pa-resource-record-plain~about')
        ->and($crawler->filter('input[data-model$="data.house"]')->attr('value'))->toBe('plain')
        ->and($crawler->filter('input[data-model$="data.year"]')->attr('value'))->toBe('about');
});

it('refuses a composite record the many-to-many parent does not hold', function (): void {
    [$client] = compositeTargetManagerClient();

    $client->request('GET', '/key/galleries/2/gallery-imprints/plain~about/edit');

    // The second gallery holds the exotic imprint alone, so the parent scope
    // is what turns a well-formed key into a miss.
    expect($client->getResponse()->getStatusCode())->toBe(404);

    $client->request('GET', '/key/galleries/2/gallery-imprints/a=7Eb~c=3Dd=20=C5=BE/edit');

    expect($client->getResponse()->getStatusCode())->toBe(200);
});

it('creates a composite record under the many-to-many parent the page was opened from', function (): void {
    [$client, $entityManager] = compositeTargetManagerClient();

    $component = $this->createLiveComponent(CreateGalleryImprint::class, [
        'parentRecords' => ['gallery' => '1'],
        'panelId' => 'key',
    ], client: $client);

    $component->set('data', ['house' => 'new.example', 'year' => '2027', 'label' => 'Created']);
    $component->call('create');

    // Nothing of the new record's identifier comes from the parent, so the
    // attach is a join-table row rather than a field — and the redirect is
    // what says the new pair was keyed for the nested route.
    expect(compositeTargetManagerLabelsOf($entityManager, 1))->toBe(['Created', 'Escaped', 'Plain'])
        ->and($component->response()->isRedirection())->toBeTrue()
        ->and((string) $component->response()->headers->get('Location'))
        ->toEndWith('/key/galleries/1/gallery-imprints/new=2Eexample~2027/edit');
});

it('detaches a selection of composite records the bulk action never hydrates', function (): void {
    [$client, $entityManager] = compositeTargetManagerClient();

    $component = $this->createLiveComponent(GalleryImprintsRelationManager::class, [
        'ownerRecord' => '1',
        'pageClass' => EditGallery::class,
        'fetchesSelectedRecords' => false,
    ], client: $client)
        ->set('selectedTableRecords', ['plain~about'])
        ->call('mountAction', ['name' => 'detach', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    // The by-key arm: the selection stays a list of strings and each one is
    // turned back into a reference, which for a pair is a two-field
    // identifier rather than anything an integer cast could stand in for.
    expect($component->component()->mountedActions)->toBe([])
        ->and(compositeTargetManagerLabelsOf($entityManager, 1))->toBe(['Escaped']);
});
