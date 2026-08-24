<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use LogicException;
use Sylament\Actions\Action;

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
