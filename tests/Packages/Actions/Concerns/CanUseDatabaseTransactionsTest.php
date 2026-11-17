<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Actions\ProductWriterHost;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\DeletingProductTable;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;

it('wraps its call in a transaction only when told to', function (): void {
    $tester = $this->createLiveComponent(ProductWriterHost::class)->callAction('transact');

    expect($this->statementsRun())->toBe(['"START TRANSACTION"', '"COMMIT"']);

    $tester->callAction('idle');

    expect($this->statementsRun())->toBe([]);
});

it('rolls back what it wrote before it threw only when told to use a transaction, letting the exception through either way', function (): void {
    expect(fn (): mixed => $this->createLiveComponent(ProductWriterHost::class)->callAction('writeAndFail'))->toThrow(RuntimeException::class, 'The write failed.')
        ->and(fn (): mixed => $this->createLiveComponent(ProductWriterHost::class)->callAction('transactAndFail'))->toThrow(RuntimeException::class, 'The write failed.')
        ->and($this->statementsRun())->toContain('"ROLLBACK"')
        ->and($this->entityManager()->isOpen())->toBeTrue()
        ->and(array_map(static fn (Product $product): string => $product->name, $this->entityManager()->getRepository(Product::class)->findAll()))->toBe(['writeAndFail']);
});

it("opens its transaction on its table's manager over the default one", function (): void {
    $tableManager = $this->platformEntityManager(TestPlatform::Sqlite);
    $action = Action::make('archive')->databaseTransaction()->table(
        Table::make(new DeletingProductTable($this->entityManager(), new HookLog()))
            ->query($tableManager->createQueryBuilder()->select('product')->from(Product::class, 'product')),
    );

    $action->beginDatabaseTransaction();

    expect($tableManager->getConnection()->isTransactionActive())->toBeTrue()
        ->and($this->entityManager()->getConnection()->isTransactionActive())->toBeFalse();

    $action->rollBackDatabaseTransaction();

    expect($tableManager->getConnection()->isTransactionActive())->toBeFalse();
});

it('refuses a transaction to an action built without the container', function (): void {
    expect(fn (): null => new Action()->databaseTransaction()->beginDatabaseTransaction())
        ->toThrow(LogicException::class, 'databaseTransaction() needs the container-built action');
});
