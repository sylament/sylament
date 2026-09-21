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
 * The two refused parents and the one that passes, in one database: a stamp
 * under a catalogue (the derived identity), a colophon of the same catalogue
 * (the single derived identity) and a line under each.
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
 * @param class-string<Resource> $class
 */
function compositeGuardResource(string $class): Resource
{
    $registry = test()->getContainer()->get(ResourceRegistry::class);

    expect($registry)->toBeInstanceOf(ResourceRegistry::class);

    return $registry->get($class);
}

function compositeGuardRootMessage(Throwable $throwable): string
{
    while (($previous = $throwable->getPrevious()) instanceof Throwable) {
        $throwable = $previous;
    }

    return $throwable->getMessage();
}

it('refuses to scope a nested resource to a derived-identity parent record', function (): void {
    [, $entityManager] = compositeGuardParentClient();

    $stamp = $entityManager->getRepository(Stamp::class)->findOneBy(['caption' => 'Alpha']);

    expect($stamp)->toBeInstanceOf(Stamp::class);

    expect(fn (): mixed => compositeGuardResource(StampMarkResource::class)->getParentScopedRecordQuery($stamp))
        ->toThrow(
            LogicException::class,
            'Resource [Sylament\Panels\Tests\Fixtures\Resources\StampMarkResource] nests under [Sylament\Panels\Tests\Fixtures\Resources\StampResource], whose records of [Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp] are not keyed by a single scalar identifier; scoping a nested resource to such a parent record is not built yet.',
        );
});

it('refuses to scope a nested resource to a single-derived-identity parent record', function (): void {
    // `Colophon` is keyed by one association and nothing else, which Doctrine
    // reports as *not* composite — so this is the arm a guard written on
    // `isIdentifierComposite` alone lets through to the relation layer.
    [, $entityManager] = compositeGuardParentClient();

    $colophon = $entityManager->getRepository(Colophon::class)->findOneBy(['note' => 'Note']);

    expect($colophon)->toBeInstanceOf(Colophon::class)
        ->and($entityManager->getClassMetadata(Colophon::class)->isIdentifierComposite)->toBeFalse();

    expect(fn (): mixed => compositeGuardResource(ColophonLineResource::class)->getParentScopedRecordQuery($colophon))
        ->toThrow(
            LogicException::class,
            'Resource [Sylament\Panels\Tests\Fixtures\Resources\ColophonLineResource] nests under [Sylament\Panels\Tests\Fixtures\Resources\ColophonResource], whose records of [Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Colophon] are not keyed by a single scalar identifier; scoping a nested resource to such a parent record is not built yet.',
        );
});

it('answers a nested page under a derived-identity parent with a 500 carrying the panels refusal', function (): void {
    [$client] = compositeGuardParentClient();

    // The refusal is a `LogicException` out of a page render, which
    // `PageController` rethrows only for an `HttpExceptionInterface` — so what
    // the user sees is a 500, not a 404.
    $client->catchExceptions(true);
    $client->request('GET', '/key/catalogues/1/stamps/1~A=2FB=20C/stamp-marks/1/edit');

    expect($client->getResponse()->getStatusCode())->toBe(500);

    $client->catchExceptions(false);

    try {
        $client->request('GET', '/key/catalogues/1/stamps/1~A=2FB=20C/stamp-marks/1/edit');

        $message = null;
    } catch (Throwable $throwable) {
        $message = compositeGuardRootMessage($throwable);
    }

    expect($message)->toBe('Resource [Sylament\Panels\Tests\Fixtures\Resources\StampMarkResource] nests under [Sylament\Panels\Tests\Fixtures\Resources\StampResource], whose records of [Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp] are not keyed by a single scalar identifier; scoping a nested resource to such a parent record is not built yet.');
});

it('answers a nested page under a single-derived-identity parent with the same refusal', function (): void {
    [$client] = compositeGuardParentClient();

    $client->catchExceptions(false);

    try {
        $client->request('GET', '/key/colophons/1/colophon-lines/1/edit');

        $message = null;
    } catch (Throwable $throwable) {
        $message = compositeGuardRootMessage($throwable);
    }

    expect($message)->toBe('Resource [Sylament\Panels\Tests\Fixtures\Resources\ColophonLineResource] nests under [Sylament\Panels\Tests\Fixtures\Resources\ColophonResource], whose records of [Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Colophon] are not keyed by a single scalar identifier; scoping a nested resource to such a parent record is not built yet.');
});

it('refuses to attach a record to a derived-identity parent record', function (): void {
    [, $entityManager] = compositeGuardParentClient();

    $stamp = $entityManager->getRepository(Stamp::class)->findOneBy(['caption' => 'Alpha']);

    expect($stamp)->toBeInstanceOf(Stamp::class);

    expect(function () use ($stamp): void {
        compositeGuardResource(StampMarkResource::class)->attachRecordToParent(new StampMark(), $stamp);
    })
        ->toThrow(
            LogicException::class,
            'Resource [Sylament\Panels\Tests\Fixtures\Resources\StampMarkResource] nests under [Sylament\Panels\Tests\Fixtures\Resources\StampResource], whose records of [Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp] are not keyed by a single scalar identifier; scoping a nested resource to such a parent record is not built yet.',
        );
});

it('refuses to attach a record to a single-derived-identity parent record', function (): void {
    [, $entityManager] = compositeGuardParentClient();

    $colophon = $entityManager->getRepository(Colophon::class)->findOneBy(['note' => 'Note']);

    expect($colophon)->toBeInstanceOf(Colophon::class);

    expect(function () use ($colophon): void {
        compositeGuardResource(ColophonLineResource::class)->attachRecordToParent(new ColophonLine(), $colophon);
    })
        ->toThrow(
            LogicException::class,
            'Resource [Sylament\Panels\Tests\Fixtures\Resources\ColophonLineResource] nests under [Sylament\Panels\Tests\Fixtures\Resources\ColophonResource], whose records of [Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Colophon] are not keyed by a single scalar identifier; scoping a nested resource to such a parent record is not built yet.',
        );
});

it('characterizes: a create page under a parent it cannot scope to still renders', function (string $url): void {
    // A create page resolves the parent record but scopes no query through it,
    // so the two scope-time guards leave it whole and the refusal is the
    // write's to carry.
    [$client] = compositeGuardParentClient();

    $client->request('GET', $url);

    expect($client->getResponse()->getStatusCode())->toBe(200);
})->with([
    'a derived-identity parent' => ['/key/catalogues/1/stamps/1~A=2FB=20C/stamp-marks/create'],
    'a single-derived-identity parent' => ['/key/colophons/1/colophon-lines/create'],
]);

it('refuses the write that create page submits and leaves the table as it found it', function (string $pageClass, array $parentRecords, array $data, string $recordClass, string $message): void {
    [$client, $entityManager] = compositeGuardParentClient();

    $before = $entityManager->getRepository($recordClass)->count();

    $component = $this->createLiveComponent($pageClass, [
        'parentRecords' => $parentRecords,
        'panelId' => 'key',
    ], client: $client);

    $component->set('data', $data);

    // `TestLiveComponent` forces `catchExceptions(false)`, so the refusal
    // escapes `call()` as itself rather than reaching BrowserKit as a 500.
    expect(function () use ($component): void {
        $component->call('create');
    })->toThrow(LogicException::class, $message);

    $entityManager->clear();

    expect($entityManager->getRepository($recordClass)->count())->toBe($before);
})->with([
    'a derived-identity parent' => [
        CreateStampMark::class,
        ['catalogue' => '1', 'stamp' => '1~A=2FB=20C'],
        ['label' => 'Written'],
        StampMark::class,
        'Resource [Sylament\Panels\Tests\Fixtures\Resources\StampMarkResource] nests under [Sylament\Panels\Tests\Fixtures\Resources\StampResource], whose records of [Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp] are not keyed by a single scalar identifier; scoping a nested resource to such a parent record is not built yet.',
    ],
    'a single-derived-identity parent' => [
        CreateColophonLine::class,
        ['colophon' => '1'],
        ['text' => 'Written'],
        ColophonLine::class,
        'Resource [Sylament\Panels\Tests\Fixtures\Resources\ColophonLineResource] nests under [Sylament\Panels\Tests\Fixtures\Resources\ColophonResource], whose records of [Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Colophon] are not keyed by a single scalar identifier; scoping a nested resource to such a parent record is not built yet.',
    ],
]);

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
