<?php

declare(strict_types=1);

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Sylament\Tests\Fixtures\Infrastructure\Entity\Tally;
use Sylament\Tests\Infrastructure\Doctrine\TestDatabase;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;

it('pins a SQLite manager to an in-memory schema of its own, whatever platform the world runs on', function (): void {
    $sqlite = $this->platformEntityManager(TestPlatform::Sqlite);
    $sqlite->persist(new Tally());
    $sqlite->flush();

    expect($sqlite->getConnection()->getDatabasePlatform())->toBeInstanceOf(SQLitePlatform::class)
        ->and($sqlite->getRepository(Tally::class)->count())->toBe(1)
        ->and($this->entityManager()->getRepository(Tally::class)->count())->toBe(0)
        ->and($this->entityManager()->getConnection()->getDatabasePlatform())->toBeInstanceOf(match (TestPlatform::current()) {
            TestPlatform::Sqlite => SQLitePlatform::class,
            TestPlatform::MySql => AbstractMySQLPlatform::class,
            TestPlatform::PostgreSql => PostgreSQLPlatform::class,
        });
});

it('keeps what a pinned manager runs out of the recorder', function (): void {
    $this->entityManager()->getRepository(Tally::class)->count();
    $this->platformEntityManager(TestPlatform::Sqlite)->getRepository(Tally::class)->count();

    expect($this->statementsRun())->toHaveCount(1);
});

it("keeps a pinned manager off the world's metadata cache, second-level cache and repository services", function (TestPlatform $platform): void {
    $configuration = $this->platformEntityManager($platform)->getConfiguration();

    expect($this->entityManager()->getConfiguration()->getMetadataCache())->toBe(TestDatabase::metadataCache())
        ->and($configuration->getMetadataCache())->not->toBe(TestDatabase::metadataCache())
        ->and($configuration->isSecondLevelCacheEnabled())->toBeFalse()
        ->and($configuration->getRepositoryFactory())->toBeInstanceOf(DefaultRepositoryFactory::class);
})->with([
    'SQLite' => [TestPlatform::Sqlite],
    'PostgreSQL' => [TestPlatform::PostgreSql],
]);

it('enforces foreign keys on the pinned SQLite manager, as MySQL and PostgreSQL do', function (): void {
    expect($this->platformEntityManager(TestPlatform::Sqlite)->getConnection()->fetchOne('PRAGMA foreign_keys'))->toBe(1);
});

it('pins a server manager that reads its platform and the mapping without ever connecting', function (TestPlatform $platform, string $platformClass): void {
    $manager = $this->platformEntityManager($platform);

    expect($manager->getConnection()->getDatabasePlatform())->toBeInstanceOf($platformClass)
        ->and($manager->getClassMetadata(Tally::class)->getTableName())->toBe('Tally')
        ->and($manager->getConnection()->isConnected())->toBeFalse();
})->with([
    'MySQL' => [TestPlatform::MySql, AbstractMySQLPlatform::class],
    'PostgreSQL' => [TestPlatform::PostgreSql, PostgreSQLPlatform::class],
]);
