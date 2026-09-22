<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Forms\Components\Select;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Binding;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Motif;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\StampMark;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditBindery;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditStamp;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ManageBinderyBindings;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ManageStampMarks;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\ImprintBindingsRelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\ImprintMotifsRelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\StampMarksRelationManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The relationship tier with the composite record on the near side: an owner
 * keyed by two plain fields over both arms, and the derived identity over the
 * one-to-many. Every write verb the two arms carry is driven through
 * `mountAction()` / `callMountedAction()`, which is the path a live request
 * takes once it has hydrated.
 *
 * Kernel reboot is disabled throughout: the fixture database lives in memory
 * on a container service. Owner keys are literals — `a=7Eb~c=3Dd=20=C5=BE` for
 * the imprint `a~b` / `c=d ž`, `plain~about` for the other, `1~A=2FB=20C` for
 * the stamp whose code reads `A/B C` under catalogue 1.
 */

/**
 * Two imprints over three bindings and three motifs: the second imprint holds
 * one of each, and one motif hangs off nobody — so the first owner's
 * candidates are exactly what it lacks, and a scoping miss shows as a row the
 * other owner holds.
 *
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function compositeOwnerManagerClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $exotic = new Imprint()->setHouse('a~b')->setYear('c=d ž')->setLabel('Exotic');
    $plain = new Imprint()->setHouse('plain')->setYear('about')->setLabel('Plain');

    $rosette = new Motif()->setName('Rosette');
    $fleuron = new Motif()->setName('Fleuron');
    $free = new Motif()->setName('Free');

    $exotic->addMotif($rosette);
    $plain->addMotif($fleuron);

    EntityManagerFactory::seed(
        $entityManager,
        $exotic,
        $plain,
        $rosette,
        $fleuron,
        $free,
        new Binding()->setImprint($exotic)->setTitle('Quarter'),
        new Binding()->setImprint($plain)->setTitle('Rival'),
        new Binding()->setTitle('Orphan'),
    );

    return [$client, $entityManager];
}

/**
 * One catalogue owning the stamp whose marks the derived-identity arm lists.
 */
function compositeOwnerManagerStamp(EntityManagerInterface $entityManager): void
{
    $catalogue = new Catalogue()->setTitle('First');
    $stamp = new Stamp()->setCatalogue($catalogue)->setCode('A/B C')->setCaption('Alpha');

    EntityManagerFactory::seed(
        $entityManager,
        $catalogue,
        $stamp,
        new StampMark()->setStamp($stamp)->setLabel('Mark A1'),
    );
}

/**
 * The titles one imprint's bindings carry, read back from the database rather
 * than from the collection the write touched.
 *
 * @return list<string>
 */
function compositeOwnerManagerBindingsOf(EntityManagerInterface $entityManager, string $label): array
{
    $entityManager->clear();

    $bindings = $entityManager->getRepository(Binding::class)->findBy([], ['title' => 'ASC']);
    $titles = [];

    foreach ($bindings as $binding) {
        if ($binding->getImprint()?->getLabel() === $label) {
            $titles[] = $binding->getTitle();
        }
    }

    return $titles;
}

/**
 * @return list<string>
 */
function compositeOwnerManagerMotifsOf(EntityManagerInterface $entityManager, string $house, string $year): array
{
    $entityManager->clear();

    $imprint = $entityManager->find(Imprint::class, ['house' => $house, 'year' => $year]);

    expect($imprint)->toBeInstanceOf(Imprint::class);

    return array_map(static fn (Motif $motif): string => $motif->getName(), $imprint?->getMotifs()->toArray() ?? []);
}

function compositeOwnerManagerRecordSelect(RelationManager $manager): Select
{
    $select = $manager->getMountedActionSchema()?->getComponent('recordId');

    return $select instanceof Select ? $select : throw new LogicException('The modal carries no record select.');
}

it('lists only the bindings of the scalar-pair owner the manager was mounted for', function (): void {
    [$client] = compositeOwnerManagerClient();

    $html = (string) $this->createLiveComponent(
        ImprintBindingsRelationManager::class,
        ['ownerRecord' => 'a=7Eb~c=3Dd=20=C5=BE', 'pageClass' => EditBindery::class],
        client: $client,
    )->render();

    expect($html)->toContain('Quarter')
        ->and($html)->not->toContain('Rival')
        ->and($html)->not->toContain('Orphan');
});

it('creates a binding through the modal already attached to the scalar-pair owner', function (): void {
    [$client, $entityManager] = compositeOwnerManagerClient();

    $component = $this->createLiveComponent(
        ImprintBindingsRelationManager::class,
        ['ownerRecord' => 'a=7Eb~c=3Dd=20=C5=BE', 'pageClass' => EditBindery::class],
        client: $client,
    )
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => ['table' => true]])
        ->set('mountedActionsData.0.title', 'Created')
        ->call('callMountedAction');

    expect($component->component()->mountedActions)->toBe([])
        ->and(compositeOwnerManagerBindingsOf($entityManager, 'Exotic'))->toBe(['Created', 'Quarter'])
        ->and(compositeOwnerManagerBindingsOf($entityManager, 'Plain'))->toBe(['Rival']);
});

it('offers the bindings the scalar-pair owner lacks and associates the one chosen', function (): void {
    [$client, $entityManager] = compositeOwnerManagerClient();

    $component = $this->createLiveComponent(
        ImprintBindingsRelationManager::class,
        ['ownerRecord' => 'a=7Eb~c=3Dd=20=C5=BE', 'pageClass' => EditBindery::class],
        client: $client,
    )
        ->call('mountAction', ['name' => 'associate', 'arguments' => [], 'context' => ['table' => true]]);

    // The candidate query is the same correlated subselect negated, so it keeps
    // the rows nobody owns as well as the rows another owner holds.
    expect(compositeOwnerManagerRecordSelect($component->component())->getOptions())
        ->toBe(['3' => 'Orphan', '2' => 'Rival']);

    $component->set('mountedActionsData.0.recordId', '3')->call('callMountedAction');

    expect(compositeOwnerManagerBindingsOf($entityManager, 'Exotic'))->toBe(['Orphan', 'Quarter'])
        ->and(compositeOwnerManagerBindingsOf($entityManager, 'Plain'))->toBe(['Rival']);
});

it('edits a row of the scalar-pair owner through the table action', function (): void {
    [$client, $entityManager] = compositeOwnerManagerClient();

    $this->createLiveComponent(
        ImprintBindingsRelationManager::class,
        ['ownerRecord' => 'a=7Eb~c=3Dd=20=C5=BE', 'pageClass' => EditBindery::class],
        client: $client,
    )
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => '1']])
        ->set('mountedActionsData.0.title', 'Renamed')
        ->call('callMountedAction');

    expect(compositeOwnerManagerBindingsOf($entityManager, 'Exotic'))->toBe(['Renamed']);
});

it('dissociates one row of the scalar-pair owner and deletes another', function (): void {
    [$client, $entityManager] = compositeOwnerManagerClient();

    $this->createLiveComponent(
        ImprintBindingsRelationManager::class,
        ['ownerRecord' => 'a=7Eb~c=3Dd=20=C5=BE', 'pageClass' => EditBindery::class],
        client: $client,
    )
        ->call('mountAction', ['name' => 'dissociate', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => '1']])
        ->call('callMountedAction');

    expect(compositeOwnerManagerBindingsOf($entityManager, 'Exotic'))->toBe([])
        ->and($entityManager->getRepository(Binding::class)->findOneBy(['title' => 'Quarter']))->not->toBeNull();

    $this->createLiveComponent(
        ImprintBindingsRelationManager::class,
        ['ownerRecord' => 'plain~about', 'pageClass' => EditBindery::class],
        client: $client,
    )
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => '2']])
        ->call('callMountedAction');

    $entityManager->clear();

    expect($entityManager->getRepository(Binding::class)->findOneBy(['title' => 'Rival']))->toBeNull();
});

it('dissociates a whole selection of the scalar-pair owner and clears it', function (): void {
    [$client, $entityManager] = compositeOwnerManagerClient();

    $component = $this->createLiveComponent(
        ImprintBindingsRelationManager::class,
        ['ownerRecord' => 'a=7Eb~c=3Dd=20=C5=BE', 'pageClass' => EditBindery::class],
        client: $client,
    )
        ->set('selectedTableRecords', ['1'])
        ->call('mountAction', ['name' => 'dissociate', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    expect($component->component()->mountedActions)->toBe([])
        ->and($component->component()->selectedTableRecordKeys)->toBe([])
        ->and(compositeOwnerManagerBindingsOf($entityManager, 'Exotic'))->toBe([])
        ->and(compositeOwnerManagerBindingsOf($entityManager, 'Plain'))->toBe(['Rival']);
});

it('lists only the motifs the scalar-pair owner holds through the join table', function (): void {
    [$client] = compositeOwnerManagerClient();

    $html = (string) $this->createLiveComponent(
        ImprintMotifsRelationManager::class,
        ['ownerRecord' => 'a=7Eb~c=3Dd=20=C5=BE', 'pageClass' => EditBindery::class],
        client: $client,
    )->render();

    // The join table carries one column per identifier field on the owner's
    // side, so a scope reading one of them would list the other imprint's
    // motif as well.
    expect($html)->toContain('Rosette')
        ->and($html)->not->toContain('Fleuron')
        ->and($html)->not->toContain('Free');
});

it('offers the motifs the scalar-pair owner lacks and attaches the one chosen', function (): void {
    [$client, $entityManager] = compositeOwnerManagerClient();

    $component = $this->createLiveComponent(
        ImprintMotifsRelationManager::class,
        ['ownerRecord' => 'a=7Eb~c=3Dd=20=C5=BE', 'pageClass' => EditBindery::class],
        client: $client,
    )
        ->call('mountAction', ['name' => 'attach', 'arguments' => [], 'context' => ['table' => true]]);

    expect(compositeOwnerManagerRecordSelect($component->component())->getOptions())
        ->toBe(['2' => 'Fleuron', '3' => 'Free']);

    $component->set('mountedActionsData.0.recordId', '3')->call('callMountedAction');

    expect(compositeOwnerManagerMotifsOf($entityManager, 'a~b', 'c=d ž'))->toBe(['Free', 'Rosette'])
        ->and(compositeOwnerManagerMotifsOf($entityManager, 'plain', 'about'))->toBe(['Fleuron']);
});

it('detaches exactly the motif a table-context action names', function (): void {
    [$client, $entityManager] = compositeOwnerManagerClient();

    $component = $this->createLiveComponent(
        ImprintMotifsRelationManager::class,
        ['ownerRecord' => 'a=7Eb~c=3Dd=20=C5=BE', 'pageClass' => EditBindery::class],
        client: $client,
    )
        ->call('mountAction', ['name' => 'detach', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => '1']])
        ->call('callMountedAction');

    expect($component->component()->mountedActions)->toBe([])
        ->and(compositeOwnerManagerMotifsOf($entityManager, 'a~b', 'c=d ž'))->toBe([])
        ->and(compositeOwnerManagerMotifsOf($entityManager, 'plain', 'about'))->toBe(['Fleuron']);
});

it('detaches a selection of motifs the bulk action never hydrates', function (): void {
    [$client, $entityManager] = compositeOwnerManagerClient();

    $component = $this->createLiveComponent(ImprintMotifsRelationManager::class, [
        'ownerRecord' => 'a=7Eb~c=3Dd=20=C5=BE',
        'pageClass' => EditBindery::class,
        'fetchesSelectedRecords' => false,
    ], client: $client)
        ->set('selectedTableRecords', ['1'])
        ->call('mountAction', ['name' => 'detach', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    // The by-key arm turns each selected key back into a reference and removes
    // the join row for an owner it never loaded either.
    expect($component->component()->mountedActions)->toBe([])
        ->and(compositeOwnerManagerMotifsOf($entityManager, 'a~b', 'c=d ž'))->toBe([])
        ->and(compositeOwnerManagerMotifsOf($entityManager, 'plain', 'about'))->toBe(['Fleuron']);
});

it('serves the routed host of the tier over a scalar-pair owner and writes through it', function (): void {
    [$client, $entityManager] = compositeOwnerManagerClient();

    $crawler = $client->request('GET', '/key/binderies/a=7Eb~c=3Dd=20=C5=BE/bindings');
    $rows = $crawler->filter('tbody .sy-ta-row');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($rows->count())->toBe(1)
        ->and(implode(' ', $rows->each(static fn (Crawler $row): string => $row->text())))->toContain('Quarter');

    $this->createLiveComponent(
        ManageBinderyBindings::class,
        ['record' => 'a=7Eb~c=3Dd=20=C5=BE', 'panelId' => 'key'],
        client: $client,
    )
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => ['table' => true]])
        ->set('mountedActionsData.0.title', 'From the page')
        ->call('callMountedAction');

    expect(compositeOwnerManagerBindingsOf($entityManager, 'Exotic'))->toBe(['From the page', 'Quarter']);
});

it('creates a mark through the modal of a derived-identity owner', function (): void {
    [$client, $entityManager] = compositeOwnerManagerClient();

    compositeOwnerManagerStamp($entityManager);

    $this->createLiveComponent(
        StampMarksRelationManager::class,
        ['ownerRecord' => '1~A=2FB=20C', 'pageClass' => EditStamp::class],
        client: $client,
    )
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => ['table' => true]])
        ->set('mountedActionsData.0.label', 'Created')
        ->call('callMountedAction');

    $entityManager->clear();

    $row = $entityManager->getConnection()->fetchAssociative(
        'SELECT stamp_catalogue_id, stamp_code FROM StampMark WHERE label = ?',
        ['Created'],
    );

    // Both halves of the owner's identifier land on the row, the code half as
    // the column stores it rather than as the record reads it back.
    expect($row)->toBe(['stamp_catalogue_id' => 1, 'stamp_code' => 'a/b c']);
});

it('deletes a mark through the routed host of a derived-identity owner', function (): void {
    [$client, $entityManager] = compositeOwnerManagerClient();

    compositeOwnerManagerStamp($entityManager);

    $crawler = $client->request('GET', '/key/catalogues/1/stamps/1~A=2FB=20C/marks');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('tbody .sy-ta-row')->count())->toBe(1);

    $this->createLiveComponent(
        ManageStampMarks::class,
        ['record' => '1~A=2FB=20C', 'parentRecords' => ['catalogue' => '1'], 'panelId' => 'key'],
        client: $client,
    )
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => '1']])
        ->call('callMountedAction');

    $entityManager->clear();

    expect($entityManager->getRepository(StampMark::class)->findOneBy(['label' => 'Mark A1']))->toBeNull();
});
