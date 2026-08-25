<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use LogicException;
use stdClass;
use Sylament\Actions\Action;
use Sylament\Actions\Tests\Fixtures\Concerns\CanUseDatabaseTransactionsFixture;

it('runs no transaction by default', function (): void {
    expect(Action::make('delete')->hasDatabaseTransactions())->toBeFalse();
});

it('turns transactions on fluently and evaluates a closure condition', function (): void {
    $action = Action::make('delete');

    expect($action->databaseTransaction())->toBe($action)
        ->and($action->hasDatabaseTransactions())->toBeTrue()
        ->and($action->databaseTransaction(false)->hasDatabaseTransactions())->toBeFalse()
        ->and($action->databaseTransaction(fn (): bool => true)->hasDatabaseTransactions())->toBeTrue();
});

it('leaves every transaction method a no-op while transactions are off', function (): void {
    $action = Action::make('delete');

    $action->beginDatabaseTransaction();
    $action->commitDatabaseTransaction();
    $action->rollBackDatabaseTransaction();

    expect($action->hasDatabaseTransactions())->toBeFalse();
});

it('fails fast when a transactional action has no injected manager registry', function (): void {
    $action = Action::make('delete')->databaseTransaction();

    expect(fn () => $action->beginDatabaseTransaction())
        ->toThrow(LogicException::class, 'databaseTransaction() needs the container-built action');
});

it('resolves the entity manager for a record class through the registry', function (): void {
    $entityManager = $this->createMock(EntityManagerInterface::class);

    $askedFor = [];

    $registry = $this->createMock(ManagerRegistry::class);
    $registry->method('getManagerForClass')->willReturnCallback(
        function (string $class) use (&$askedFor, $entityManager): EntityManagerInterface {
            $askedFor[] = $class;

            return $entityManager;
        },
    );

    $fixture = new CanUseDatabaseTransactionsFixture();
    $fixture->setManagerRegistry($registry);

    expect($fixture->exposeEntityManagerForRecord(stdClass::class))->toBe($entityManager)
        ->and($fixture->exposeEntityManagerForRecord(new stdClass()))->toBe($entityManager)
        ->and($askedFor)->toBe([stdClass::class, stdClass::class]);
});

it('fails fast when resolving a record manager with no injected registry', function (): void {
    $fixture = new CanUseDatabaseTransactionsFixture();

    expect(fn (): mixed => $fixture->exposeEntityManagerForRecord(stdClass::class))
        ->toThrow(LogicException::class, 'The action [persist] persists a record, which needs the container-built instance');
});

it('throws when no Doctrine manager handles the record class', function (): void {
    $registry = $this->createMock(ManagerRegistry::class);
    $registry->method('getManagerForClass')->willReturn(null);

    $fixture = new CanUseDatabaseTransactionsFixture();
    $fixture->setManagerRegistry($registry);

    expect(fn (): mixed => $fixture->exposeEntityManagerForRecord(stdClass::class))
        ->toThrow(LogicException::class, 'No Doctrine manager handles stdClass.');
});

it('throws when the manager handling the record class is not an ORM entity manager', function (): void {
    $registry = $this->createMock(ManagerRegistry::class);
    $registry->method('getManagerForClass')->willReturn($this->createMock(ObjectManager::class));

    $fixture = new CanUseDatabaseTransactionsFixture();
    $fixture->setManagerRegistry($registry);

    expect(fn (): mixed => $fixture->exposeEntityManagerForRecord(stdClass::class))
        ->toThrow(LogicException::class, 'The action [persist] needs an ORM entity manager for stdClass');
});
