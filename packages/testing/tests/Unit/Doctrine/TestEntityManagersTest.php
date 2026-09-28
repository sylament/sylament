<?php

declare(strict_types=1);

use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Types\Type;
use Sylament\Testing\Doctrine\RecordingMiddleware;
use Sylament\Testing\Doctrine\TestEntityManagers;
use Sylament\Testing\Doctrine\TestPlatform;
use Sylament\Testing\Tests\Fixtures\Doctrine\Entity\Shelf;
use Sylament\Testing\Tests\Fixtures\Doctrine\ShoutType;

/**
 * @return list<string>
 */
function testingEntityPaths(): array
{
    return [dirname(__DIR__, 2) . '/Fixtures/Doctrine/Entity'];
}

it('builds an in-memory SQLite manager carrying the package schema', function (): void {
    $entityManager = TestEntityManagers::createSqlite('testing', testingEntityPaths());
    $entityManager->persist(new Shelf('poetry'));
    $entityManager->flush();
    $entityManager->clear();

    expect($entityManager->getConnection()->getDatabasePlatform())->toBeInstanceOf(SQLitePlatform::class)
        ->and(array_map(static fn (Shelf $shelf): string => $shelf->getLabel(), $entityManager->getRepository(Shelf::class)->findAll()))
        ->toBe(['poetry']);
});

it('gives every manager a database of its own', function (): void {
    $first = TestEntityManagers::createSqlite('testing', testingEntityPaths());
    $second = TestEntityManagers::createSqlite('testing', testingEntityPaths());

    $first->persist(new Shelf('poetry'));
    $first->flush();

    expect($second->getRepository(Shelf::class)->findAll())->toBe([]);
});

it('records the statements a test runs and never the schema build', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = TestEntityManagers::createSqlite('testing', testingEntityPaths(), recorder: $recorder);

    $afterBuild = $recorder->statements();
    $entityManager->getRepository(Shelf::class)->findAll();

    expect($afterBuild)->toBe([])
        ->and($recorder->statements())->toHaveCount(1)
        ->and($recorder->statements()[0])->toStartWith('SELECT');
});

it('registers a custom type once, however many managers name it', function (): void {
    TestEntityManagers::createSqlite('testing', testingEntityPaths(), [ShoutType::NAME => ShoutType::class]);
    TestEntityManagers::createSqlite('testing', testingEntityPaths(), [ShoutType::NAME => ShoutType::class]);

    expect(Type::getType(ShoutType::NAME))->toBeInstanceOf(ShoutType::class);
});

it('builds SQLite through create() while the url is unset', function (): void {
    $previous = getenv(TestPlatform::URL_VARIABLE);
    putenv(TestPlatform::URL_VARIABLE);

    try {
        $entityManager = TestEntityManagers::create('testing', testingEntityPaths());
    } finally {
        putenv($previous === false ? TestPlatform::URL_VARIABLE : TestPlatform::URL_VARIABLE . '=' . $previous);
    }

    expect($entityManager->getConnection()->getDatabasePlatform())->toBeInstanceOf(SQLitePlatform::class);
});
