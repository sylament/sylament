<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Sylament\Support\Tests\Doctrine\DatabasePool;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Sylament\Support\Tests\Doctrine\TestEntityManagers;
use Sylament\Support\Tests\Doctrine\TestPlatform;
use Sylament\Support\Tests\Fixtures\Doctrine\Entity\Shelf;

function pooledShelves(?RecordingMiddleware $recorder = null): EntityManager
{
    return TestEntityManagers::create('support', [dirname(__DIR__, 2) . '/Fixtures/Doctrine/Entity'], recorder: $recorder);
}

/**
 * @return list<string>
 */
function shelfLabels(EntityManager $entityManager): array
{
    return array_map(static fn (Shelf $shelf): string => $shelf->getLabel(), $entityManager->getRepository(Shelf::class)->findAll());
}

it('hands each manager of a test a database of its own', function (): void {
    $first = pooledShelves();
    $second = pooledShelves();

    $first->persist(new Shelf('first'));
    $first->flush();

    expect(shelfLabels($second))->toBe([])
        ->and($second->getConnection()->getDatabase())->not->toBe($first->getConnection()->getDatabase());
})->skip(TestPlatform::current() === TestPlatform::Sqlite, 'The pool exists only on a server; in-memory SQLite gives every manager a database of its own.');

it('wipes a database and restarts its identity counters before a later test takes it', function (): void {
    $first = pooledShelves();
    $first->persist(new Shelf('kept'));
    $first->flush();

    DatabasePool::instance()->testStarted();

    $next = pooledShelves();
    $fresh = new Shelf('fresh');
    $next->persist($fresh);
    $next->flush();

    expect(shelfLabels($next))->toBe(['fresh'])
        ->and($fresh->getId())->toBe(1);
})->skip(TestPlatform::current() === TestPlatform::Sqlite, 'The pool exists only on a server; in-memory SQLite gives every manager a database of its own.');

it('restarts a counter a rolled-back insert moved, though its table stayed empty', function (): void {
    $first = pooledShelves();
    $first->getConnection()->beginTransaction();
    $first->persist(new Shelf('rolled back'));
    $first->flush();
    $first->getConnection()->rollBack();

    DatabasePool::instance()->testStarted();

    $next = pooledShelves();
    $fresh = new Shelf('fresh');
    $next->persist($fresh);
    $next->flush();

    expect($fresh->getId())->toBe(1);
})->skip(TestPlatform::current() === TestPlatform::Sqlite, 'The pool exists only on a server; in-memory SQLite gives every manager a database of its own.');

it('does not wait on a transaction the previous test left open', function (): void {
    $first = pooledShelves();
    $first->getConnection()->beginTransaction();
    $first->persist(new Shelf('uncommitted'));
    $first->flush();

    DatabasePool::instance()->testStarted();

    $started = hrtime(true);
    $next = pooledShelves();

    expect(shelfLabels($next))->toBe([])
        ->and((hrtime(true) - $started) / 1e9)->toBeLessThan(5.0)
        ->and($first->getConnection()->isConnected())->toBeFalse();
})->skip(TestPlatform::current() === TestPlatform::Sqlite, 'The pool exists only on a server; in-memory SQLite gives every manager a database of its own.');

it('starts a slot first used late as empty as the ones before it', function (): void {
    DatabasePool::instance()->testStarted();

    [$one, $two, $three, $four] = [pooledShelves(), pooledShelves(), pooledShelves(), pooledShelves()];
    $one->persist(new Shelf('slot one'));
    $one->flush();

    expect(shelfLabels($four))->toBe([])
        ->and(shelfLabels($three))->toBe([])
        ->and(shelfLabels($two))->toBe([])
        ->and(shelfLabels($one))->toBe(['slot one']);
})->skip(TestPlatform::current() === TestPlatform::Sqlite, 'The pool exists only on a server; in-memory SQLite gives every manager a database of its own.');

it('keeps its own housekeeping out of a recorder', function (): void {
    $recorder = new RecordingMiddleware();

    pooledShelves($recorder);
    pooledShelves($recorder);

    expect($recorder->statements())->toBe([]);
})->skip(TestPlatform::current() === TestPlatform::Sqlite, 'The pool exists only on a server; in-memory SQLite gives every manager a database of its own.');

it('refuses a url that names no database to derive its own from', function (string $url): void {
    expect(fn (): DatabasePool => DatabasePool::fromUrl($url))
        ->toThrow(LogicException::class, 'SYLAMENT_TEST_DATABASE_URL must name a database the pool derives its own from');
})->with([
    'mysql' => ['mysql://root:secret@127.0.0.1:33307'],
    'postgresql' => ['postgresql://postgres:secret@127.0.0.1:55433/'],
]);
