<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ReplicateAction;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Actions\ProductWriterHost;
use Sylament\Tests\Fixtures\Actions\Record\IdlessTicket;
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
        Table::make(new DeletingProductTable($this->entityManager(), new HookLog(), $this::getContainer()->get('translator')))
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

it('refuses to write a record from an instance the container did not build, or one no Doctrine manager handles', function (): void {
    expect(fn (): mixed => new ReplicateAction()->name('replicate')->configure()->record(new Product('Lamp'))->call())
        ->toThrow(LogicException::class, 'The action [replicate] persists a record, which needs the container-built instance — register it as a service so the manager registry is injected.')
        ->and(fn (): mixed => ReplicateAction::make()->record(new IdlessTicket('T-1'))->call())
        ->toThrow(LogicException::class, 'No Doctrine manager handles ' . IdlessTicket::class . '.');
});
