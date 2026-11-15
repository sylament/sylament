<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\Tally;
use Sylament\Tests\Infrastructure\Doctrine\TestDatabase;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;

it('empties every table and restarts its identity counter before the next test', function (): void {
    $this->seed(new Tally(), new Tally(), new Tally());

    TestDatabase::testStarted();

    $fresh = new Tally();
    $this->seed($fresh);

    expect($this->entityManager()->getRepository(Tally::class)->count())->toBe(1)
        ->and($fresh->id)->toBe(1);
})->skip(TestPlatform::current() === TestPlatform::Sqlite, "The pool wipes only a server database; SQLite copies a template over the worker's file.");

it('restarts a counter a rolled-back insert moved, though its table stayed empty', function (): void {
    $connection = $this->entityManager()->getConnection();
    $connection->beginTransaction();
    $connection->executeStatement('INSERT INTO Tally (count) VALUES (0)');
    $connection->rollBack();

    TestDatabase::testStarted();

    $fresh = new Tally();
    $this->seed($fresh);

    expect($fresh->id)->toBe(1);
})->skip(TestPlatform::current() === TestPlatform::Sqlite, "The pool wipes only a server database; SQLite copies a template over the worker's file.");
