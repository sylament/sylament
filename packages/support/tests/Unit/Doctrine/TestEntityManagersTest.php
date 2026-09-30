<?php

declare(strict_types=1);

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Doctrine\ORM\Configuration;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Sylament\Support\Tests\Doctrine\TestEntityManagers;
use Sylament\Support\Tests\Doctrine\TestPlatform;
use Sylament\Support\Tests\Fixtures\Doctrine\Entity\Book;
use Sylament\Support\Tests\Fixtures\Doctrine\Entity\Shelf;
use Sylament\Support\Tests\Fixtures\Doctrine\ShoutType;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * @return list<string>
 */
function doctrineFixtureEntityPaths(): array
{
    return [dirname(__DIR__, 2) . '/Fixtures/Doctrine/Entity'];
}

it('builds an in-memory SQLite manager carrying the package schema', function (): void {
    $entityManager = TestEntityManagers::createSqlite('support', doctrineFixtureEntityPaths());
    $entityManager->persist(new Shelf('poetry'));
    $entityManager->flush();
    $entityManager->clear();

    expect($entityManager->getConnection()->getDatabasePlatform())->toBeInstanceOf(SQLitePlatform::class)
        ->and(array_map(static fn (Shelf $shelf): string => $shelf->getLabel(), $entityManager->getRepository(Shelf::class)->findAll()))
        ->toBe(['poetry']);
});

it('gives every manager a database of its own', function (): void {
    $first = TestEntityManagers::createSqlite('support', doctrineFixtureEntityPaths());
    $second = TestEntityManagers::createSqlite('support', doctrineFixtureEntityPaths());

    $first->persist(new Shelf('poetry'));
    $first->flush();

    expect($second->getRepository(Shelf::class)->findAll())->toBe([]);
});

it('records the statements a test runs and never the schema build', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = TestEntityManagers::createSqlite('support', doctrineFixtureEntityPaths(), recorder: $recorder);

    $afterBuild = $recorder->statements();
    $entityManager->getRepository(Shelf::class)->findAll();

    expect($afterBuild)->toBe([])
        ->and($recorder->statements())->toHaveCount(1)
        ->and($recorder->statements()[0])->toStartWith('SELECT');
});

it('registers a custom type once, however many managers name it', function (): void {
    TestEntityManagers::createSqlite('support', doctrineFixtureEntityPaths(), [ShoutType::NAME => ShoutType::class]);
    TestEntityManagers::createSqlite('support', doctrineFixtureEntityPaths(), [ShoutType::NAME => ShoutType::class]);

    expect(Type::getType(ShoutType::NAME))->toBeInstanceOf(ShoutType::class);
});

it('builds SQLite through create() while the url is unset', function (): void {
    $previous = getenv(TestPlatform::URL_VARIABLE);
    putenv(TestPlatform::URL_VARIABLE);

    try {
        $entityManager = TestEntityManagers::create('support', doctrineFixtureEntityPaths());
    } finally {
        putenv($previous === false ? TestPlatform::URL_VARIABLE : TestPlatform::URL_VARIABLE . '=' . $previous);
    }

    expect($entityManager->getConnection()->getDatabasePlatform())->toBeInstanceOf(SQLitePlatform::class);
});

it('enforces foreign keys on SQLite without recording the pragma that turns them on', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = TestEntityManagers::createSqlite('support', doctrineFixtureEntityPaths(), recorder: $recorder);

    $shelf = new Shelf('poetry');
    $entityManager->persist($shelf);
    $entityManager->persist(new Book('Odes', $shelf));
    $entityManager->flush();

    expect(fn (): int|string => $entityManager->getConnection()->executeStatement('DELETE FROM Shelf'))
        ->toThrow(ForeignKeyConstraintViolationException::class)
        ->and($recorder->statements())->each->not->toStartWith('PRAGMA');
});

it('hands the configuration to a callback before the manager is built, on the path the platform takes and on SQLite', function (): void {
    $configure = static function (Configuration $config): void {
        $cache = new CacheConfiguration();
        $cache->setCacheFactory(new DefaultCacheFactory(new RegionsConfiguration(), new ArrayAdapter()));

        $config->setSecondLevelCacheEnabled();
        $config->setSecondLevelCacheConfiguration($cache);
    };

    $created = TestEntityManagers::create('support', doctrineFixtureEntityPaths(), configure: $configure);
    $sqlite = TestEntityManagers::createSqlite('support', doctrineFixtureEntityPaths(), configure: $configure);

    expect($created->getConfiguration()->isSecondLevelCacheEnabled())->toBeTrue()
        ->and($sqlite->getConfiguration()->isSecondLevelCacheEnabled())->toBeTrue()
        ->and($created->getCache())->not->toBeNull()
        ->and($sqlite->getCache())->not->toBeNull()
        ->and(TestEntityManagers::createSqlite('support', doctrineFixtureEntityPaths())->getConfiguration()->isSecondLevelCacheEnabled())->toBeFalse();
});
