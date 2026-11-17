<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\BulkAction;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tests\Fixtures\Actions\TablelessBulkHost;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\SelectionReadingProductTable;

describe('reading the selection', function (): void {
    it('reads the selected records as a query too', function (): void {
        $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'), new Product('Rug'));

        $this->createLiveComponent(SelectionReadingProductTable::class)
            ->selectTableRecords([$lamp, $vase])
            ->callAction(TestAction::make('count')->table()->bulk());

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['count queried 2']);
    });

    it('refuses either reader to an action that does not access the selection', function (string $reader): void {
        expect(fn (): mixed => Action::make('archive')->{$reader}())
            ->toThrow(LogicException::class, 'The action [archive] is attempting to access the selected records from the table, but it is not using [accessSelectedRecords()], so they are not available.');
    })->with([
        'the records' => ['getSelectedRecords'],
        'their query' => ['getSelectedRecordsQuery'],
    ]);

    it('refuses the selection to an action whose host has no table', function (): void {
        expect(fn (): mixed => $this->createLiveComponent(TablelessBulkHost::class)->callAction('archiveSelected'))
            ->toThrow(LogicException::class, 'The action [archiveSelected] accesses selected records but its live component is not a table host — implement ' . InteractsWithTable::class . '.');
    });

    it('refuses the selection to an action with no host', function (): void {
        expect(fn (): mixed => BulkAction::make('archive')->getSelectedRecords())->toThrow(RuntimeException::class, 'Action is not part of LiveComponent');
    });
});

it('counts what its body reports against the selection it read', function (string $action, string $title): void {
    $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'), $rug = new Product('Rug'));

    $this->createLiveComponent(SelectionReadingProductTable::class)
        ->selectTableRecords([$lamp, $vase, $rug])
        ->callAction(TestAction::make($action)->table()->bulk())
        ->assertNotified($title);
})->with([
    'one record failed' => ['failOne', '2 of 3 done'],
    'a successful count' => ['succeedOne', '1 of 3 done'],
    'a complete failure' => ['failAll', '0 of 3 done'],
]);
