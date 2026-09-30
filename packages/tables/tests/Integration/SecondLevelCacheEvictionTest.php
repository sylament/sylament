<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Lamp;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shelf;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Stand;
use Sylament\Tables\Tests\Fixtures\Twig\Components\CachedLampsTable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The by-key writes run as one DQL or DBAL statement past the unit of work, so
 * they evict the second-level cache themselves: the delete and the one-column
 * dissociate through Doctrine's own evict hint on their DQL statement, the
 * several-column dissociate through the target's entity region after its DBAL
 * statement. No collection region is evicted, and none needs to be: an owner's
 * cached collection that lists an evicted record misses and reloads.
 */

beforeEach(function (): void {
    $request = new Request();
    $request->setSession($this::getContainer()->get('session.factory')->createSession());
    $this::getContainer()->get(RequestStack::class)->push($request);
});

/**
 * Three lamps on the first shelf and the north stand, every lamp and both
 * collections read once so the regions hold them, and the identity map cleared.
 *
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function cachedLampsSeed(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $shelf = new Shelf(1, 'Oak');
    $stand = new Stand('north', 'one', 'Brass');

    foreach ([$shelf, $stand, new Lamp(1, 'Desk', $shelf, $stand), new Lamp(2, 'Floor', $shelf, $stand), new Lamp(3, 'Wall', $shelf, $stand)] as $entity) {
        $entityManager->persist($entity);
    }

    $entityManager->flush();
    $entityManager->clear();

    foreach ([1, 2, 3] as $id) {
        $entityManager->find(Lamp::class, $id);
    }

    $entityManager->find(Shelf::class, 1)?->getLamps()->toArray();
    $entityManager->find(Stand::class, ['region' => 'north', 'code' => 'one'])?->getLamps()->toArray();
    $entityManager->clear();

    return [$client, $entityManager];
}

/**
 * @param list<string> $selection
 */
function cachedLampsRun(KernelBrowser $client, string $scope, string $action, array $selection): void
{
    test()->createLiveComponent(CachedLampsTable::class, ['scope' => $scope], client: $client)
        ->set('selectedTableRecords', $selection)
        ->call('mountAction', ['name' => $action, 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');
}

/**
 * @param iterable<Lamp> $lamps
 *
 * @return list<int>
 */
function cachedLampIds(iterable $lamps): array
{
    $ids = [];

    foreach ($lamps as $lamp) {
        $ids[] = $lamp->getId();
    }

    sort($ids);

    return $ids;
}

it('warms the regions it evicts from, so the cases below read the cache rather than the database', function (): void {
    [, $entityManager] = cachedLampsSeed();

    expect($entityManager->getCache()?->containsEntity(Lamp::class, 1))->toBeTrue()
        ->and($entityManager->getCache()?->containsCollection(Shelf::class, 'lamps', 1))->toBeTrue();
});

it('evicts the lamps a by-key delete removed, and the shelf reads its lamps afresh', function (): void {
    [$client, $entityManager] = cachedLampsSeed();

    cachedLampsRun($client, 'all', 'delete', ['1', '2']);
    $entityManager->clear();

    expect($entityManager->find(Lamp::class, 1))->toBeNull()
        ->and($entityManager->find(Lamp::class, 3)?->getLabel())->toBe('Wall')
        ->and(cachedLampIds($entityManager->find(Shelf::class, 1)?->getLamps() ?? []))->toBe([3]);
});

it('evicts the lamps a by-key dissociate wrote through DQL, over a one-column foreign key', function (): void {
    [$client, $entityManager] = cachedLampsSeed();

    cachedLampsRun($client, 'shelf', 'dissociate', ['1']);
    $entityManager->clear();

    expect($entityManager->find(Lamp::class, 1)?->getShelf())->toBeNull()
        ->and(cachedLampIds($entityManager->find(Shelf::class, 1)?->getLamps() ?? []))->toBe([2, 3]);
});

it('evicts the lamps a by-key dissociate wrote through DBAL, over a foreign key of two columns', function (): void {
    [$client, $entityManager] = cachedLampsSeed();

    cachedLampsRun($client, 'stand', 'dissociate', ['1']);
    $entityManager->clear();

    expect($entityManager->find(Lamp::class, 1)?->getStand())->toBeNull()
        ->and(cachedLampIds($entityManager->find(Stand::class, ['region' => 'north', 'code' => 'one'])?->getLamps() ?? []))->toBe([2, 3]);
});
