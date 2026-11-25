<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Notifications\Notification;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Actions\TablelessBulkHost;
use Sylament\Tests\Fixtures\Entity\CachedLantern;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\RawUuidCrate;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\SecondaryEntity\Observatory;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\ByKeyDeletingCachedLanternTable;
use Sylament\Tests\Fixtures\Tables\ByKeyDeletingInvoiceLineTable;
use Sylament\Tests\Fixtures\Tables\ByKeyDeletingInvoiceTable;
use Sylament\Tests\Fixtures\Tables\ByKeyDeletingOrderLineTable;
use Sylament\Tests\Fixtures\Tables\ByKeyDeletingProductTable;
use Sylament\Tests\Fixtures\Tables\ByKeyDeletingRawUuidCrateTable;
use Sylament\Tests\Fixtures\Tables\DeletingProductTable;
use Sylament\Tests\Fixtures\Tables\ObservatoryRecordsTable;
use Sylament\Tests\Fixtures\Tables\RecordsLaneDeletingTable;
use Sylament\Tests\Infrastructure\Doctrine\FailingWrite;
use Symfony\Component\Uid\Uuid;

describe('the trigger and its confirmation', function (): void {
    it('offers a danger "Delete selected" beside a trash icon once rows are selected, confirmed by a "Delete" headed by the plural label', function (): void {
        $this->seed($lamp = new Product('Lamp'), new Product('Vase'));

        $tester = $this->createLiveComponent(DeletingProductTable::class)->selectTableRecords([$lamp]);

        expect($tester->render()->crawler())->toContainElement(Selectors::BULK_ONLY . ':not([hidden]) ' . SupportSelectors::BUTTON . SupportSelectors::color('danger'), 'trash-20-solidDelete selected');

        $tester->mountAction(TestAction::make('delete')->table()->bulk())
            ->assertCanSeeTableRecords([$lamp]);

        expect($tester->render()->crawler())->toContainElement(SupportSelectors::MODAL_HEADING, 'Delete selected Products')
            ->toContainButtons(SupportSelectors::MODAL_FOOTER_ACTIONS, ['Cancel', 'Delete']);
    });

    it('is headed by the bare verb with no table to name its records', function (): void {
        expect($this->createLiveComponent(TablelessBulkHost::class)->mountAction('deleteSelected')->render()->crawler())
            ->toContainElement(SupportSelectors::MODAL_HEADING, 'Delete selected');
    });
});

describe('the fetching arm', function (): void {
    it('removes every selected record in one flush, keeps the rest, toasts "Deleted" and clears the selection', function (): void {
        $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'), $rug = new Product('Rug'));

        $tester = $this->createLiveComponent(DeletingProductTable::class)->selectTableRecords([$lamp, $vase]);

        $tester->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified('Deleted')
            ->assertCanSeeTableRecords([$rug])
            ->assertCanNotSeeTableRecords([$lamp, $vase]);

        expect(array_map(static fn (string $statement): string => implode(' ', array_slice(explode(' ', $statement), 0, 3)), array_values(array_filter($this->statementsRun(), static fn (string $statement): bool => !str_starts_with($statement, 'SELECT')))))->toBe(['"START TRANSACTION"', 'DELETE FROM Product', 'DELETE FROM Product', '"COMMIT"'])
            ->and($tester->render()->crawler()->filter(Selectors::BULK_ONLY . '[hidden]'))->not->toHaveCount(0);
    });

    it('removes the selected entities through the manager that maps their class', function (): void {
        $this->seed($kitt = new Observatory('Kitt Peak'), $mauna = new Observatory('Mauna Kea'), $kept = new Observatory('Paranal'));

        $this->createLiveComponent(ObservatoryRecordsTable::class)
            ->selectTableRecords([$kitt, $mauna])
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified('Deleted');

        expect(array_map(static fn (Observatory $observatory): string => $observatory->name, $this->entityManagerFor(Observatory::class)->getRepository(Observatory::class)->findAll()))->toBe([$kept->name]);
    });

    it('toasts "Deleted" and writes nothing for an empty selection', function (): void {
        $this->seed(new Product('Lamp'));

        $this->createLiveComponent(DeletingProductTable::class)
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified('Deleted');

        expect(array_values(array_filter($this->statementsRun(), static fn (string $statement): bool => !str_starts_with($statement, 'SELECT'))))->toBe([])
            ->and($this->entityManager()->getRepository(Product::class)->count())->toBe(1);
    });

    it('deletes none of the selection when one of its deletes fails, and toasts the failure instead of failing the request', function (): void {
        $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'));
        FailingWrite::afterDeletes($this->entityManager(), Product::class, allowed: 1);

        $this->createLiveComponent(DeletingProductTable::class)
            ->selectTableRecords([$lamp, $vase])
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified(Notification::make()->danger()->title('Failed to delete 2 records')->persistent())
            ->assertCanSeeTableRecords([$lamp, $vase]);
    });

    it('toasts a failure for rows that are no records it can remove', function (): void {
        $this->createLiveComponent(RecordsLaneDeletingTable::class)
            ->selectTableRecords(['lamp'])
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified(Notification::make()->danger()->title('Failed to delete 1 record')->persistent());
    });

    it('refuses to delete from an instance the container did not build', function (): void {
        $this->seed($lamp = new Product('Lamp'));

        expect(fn (): mixed => $this->createLiveComponent(DeletingProductTable::class)
            ->selectTableRecords([$lamp])
            ->callAction(TestAction::make('handBuilt')->table()->bulk()))
            ->toThrow(LogicException::class, 'DeleteBulkAction needs the container-built instance — register it as a service so the manager registry is injected.');
    });
});

describe('the by-key arm', function (): void {
    it('deletes exactly the selected keys in one statement, with no transaction around it', function (): void {
        $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'), $rug = new Product('Rug'));

        $this->createLiveComponent(ByKeyDeletingProductTable::class)
            ->selectTableRecords([$lamp, $vase])
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified('Deleted')
            ->assertCanSeeTableRecords([$rug])
            ->assertCanNotSeeTableRecords([$lamp, $vase]);

        expect(array_map(static fn (string $statement): string => implode(' ', array_slice(explode(' ', $statement), 0, 3)), array_values(array_filter($this->statementsRun(), static fn (string $statement): bool => !str_starts_with($statement, 'SELECT')))))->toBe(['DELETE FROM Product']);
    });

    it('deletes a composite-keyed selection by its keys, and only it', function (string $table, object $line, object $sameHead, object $sameLine): void {
        $this->seed($line, $sameHead, $sameLine);

        $this->createLiveComponent($table)
            ->selectTableRecords([$line])
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified('Deleted')
            ->assertCanSeeTableRecords([$sameHead, $sameLine])
            ->assertCanNotSeeTableRecords([$line]);
    })->with([
        'scalar fields' => fn (): array => [ByKeyDeletingOrderLineTable::class, new OrderLine(order: 7, line: 2), new OrderLine(order: 7, line: 3), new OrderLine(order: 8, line: 2)],
        'an association plus a scalar' => fn (): array => [ByKeyDeletingInvoiceLineTable::class, new InvoiceLine($seven = new Invoice(7), line: 2), new InvoiceLine($seven, line: 3), new InvoiceLine(new Invoice(8), line: 2)],
    ]);

    it('deletes a selection keyed by a uuid stored as raw bytes', function (): void {
        $this->seed($kept = new RawUuidCrate(Uuid::v7(), 'Kept'), $gone = new RawUuidCrate(Uuid::v7(), 'Gone'));

        $this->createLiveComponent(ByKeyDeletingRawUuidCrateTable::class)
            ->selectTableRecords([$gone])
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified('Deleted')
            ->assertCanSeeTableRecords([$kept])
            ->assertCanNotSeeTableRecords([$gone]);
    });

    it('deletes a selection too long for one statement in several, inside one transaction', function (): void {
        $lines = array_map(static fn (int $line): OrderLine => new OrderLine(order: 1, line: $line), range(1, 501));
        $this->seed($kept = new OrderLine(order: 2, line: 1), ...$lines);

        $this->createLiveComponent(ByKeyDeletingOrderLineTable::class)
            ->selectTableRecords($lines)
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified('Deleted')
            ->assertCanSeeTableRecords([$kept]);

        expect(array_map(static fn (string $statement): string => implode(' ', array_slice(explode(' ', $statement), 0, 3)), array_values(array_filter($this->statementsRun(), static fn (string $statement): bool => !str_starts_with($statement, 'SELECT')))))->toBe(['"START TRANSACTION"', 'DELETE FROM OrderLine', 'DELETE FROM OrderLine', '"COMMIT"'])
            ->and($this->entityManager()->getRepository(OrderLine::class)->count())->toBe(1);
    });

    it('evicts what it deletes from the second-level cache', function (): void {
        $this->seed($gone = new CachedLantern('Gone'), $kept = new CachedLantern('Kept'));

        $warm = $this->entityManager();
        $warm->find(CachedLantern::class, $gone->id);

        $warmed = $warm->getCache()?->containsEntity(CachedLantern::class, $gone->id);
        $warm->clear();

        // One kernel serves every request, so the cache warmed here is the one the delete writes past.
        $client = $this::getContainer()->get('test.client');
        $client->disableReboot();

        $this->createLiveComponent(ByKeyDeletingCachedLanternTable::class, client: $client)
            ->selectTableRecords([$gone])
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified('Deleted');

        $read = $this->entityManager();
        $read->clear();

        expect($warmed)->toBeTrue()
            ->and($read->find(CachedLantern::class, $gone->id))->toBeNull()
            ->and($read->find(CachedLantern::class, $kept->id)?->name)->toBe('Kept');
    });

    it('reports the rows its override deleted as the successful count', function (): void {
        $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'));

        $this->createLiveComponent(ByKeyDeletingProductTable::class)
            ->selectTableRecords([$lamp, $vase])
            ->callAction(TestAction::make('deleteShort')->table()->bulk())
            ->assertNotified(Notification::make()->warning()->title('Deleted 1 of 2')->persistent());

        expect($this->entityManager()->getRepository(Product::class)->count())->toBe(1);
    });

    it('toasts the failure when its override throws, and runs no override for an empty selection', function (): void {
        $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'));

        $tester = $this->createLiveComponent(ByKeyDeletingProductTable::class)
            ->callAction(TestAction::make('deleteRefused')->table()->bulk())
            ->assertNotified('Deleted');
        $unselected = $this::getContainer()->get(HookLog::class)->hooks;

        $tester->selectTableRecords([$lamp, $vase])
            ->callAction(TestAction::make('deleteRefused')->table()->bulk())
            ->assertNotified(Notification::make()->danger()->title('Failed to delete 2 records')->persistent())
            ->assertCanSeeTableRecords([$lamp, $vase]);

        expect($unselected)->toBe([])
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe(["refused {$lamp->id}, {$vase->id}"]);
    });

    it('deletes nothing and toasts the failure when a foreign key still references a selected row', function (): void {
        $this->seed($line = new InvoiceLine($referenced = new Invoice(7), line: 1), $free = new Invoice(8));

        $this->createLiveComponent(ByKeyDeletingInvoiceTable::class)
            ->selectTableRecords([$referenced, $free])
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertNotified(Notification::make()->danger()->title('Failed to delete 2 records')->persistent())
            ->assertCanSeeTableRecords([$referenced, $free]);
    });

    it('hands a using() that replaces it the selected keys as strings', function (): void {
        $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'));

        $this->createLiveComponent(ByKeyDeletingProductTable::class)
            ->selectTableRecords([$lamp, $vase])
            ->callAction(TestAction::make('deleteUsing')->table()->bulk())
            ->assertCanSeeTableRecords([$lamp, $vase]);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['using() was handed string, string']);
    });

    it('refuses a table with no query to delete from, or none at all', function (string $host, TestAction|string $action, string $message): void {
        $this->seed($lamp = new Product('Lamp'));

        expect(fn (): mixed => $this->createLiveComponent($host)->selectTableRecords([$host === RecordsLaneDeletingTable::class ? 'lamp' : $lamp])->callAction($action))
            ->toThrow(LogicException::class, $message);
    })->with([
        'a records() table' => fn (): array => [RecordsLaneDeletingTable::class, TestAction::make('deleteByKey')->table()->bulk(), 'The action [deleteByKey] deletes selected records by key, which needs a [query()] table.'],
        'an action outside its host table' => fn (): array => [ByKeyDeletingProductTable::class, 'loose', 'The action [loose] deletes selected records by key, which only a table can supply.'],
    ]);
});
