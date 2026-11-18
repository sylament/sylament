<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\BulkAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\TariffBand;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table over the record class keyed by several fields that `lineClass` names
 * ({@see OrderLine}, {@see InvoiceLine} or {@see TariffBand}), whose bulk actions record
 * in the {@see HookLog} how many selected records they read and how many of them are
 * distinct objects (`read`), how many keys and how many distinct ones (`readKeys`), and
 * how many rows the selection's query returns (`count`); with `refuses`, a line numbered
 * 1 cannot be selected.
 */
#[AsLiveComponent(name: 'Tables:BatchedSelectionLineTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class BatchedSelectionLineTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    /**
     * @var class-string
     */
    #[LiveProp]
    public string $lineClass = OrderLine::class;

    #[LiveProp]
    public bool $refuses = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('line')->from($this->lineClass, 'line'))
            ->columns([
                TextColumn::make($this->lineClass === TariffBand::class ? 'label' : 'note'),
            ])
            ->checkIfRecordIsSelectableUsing($this->refuses ? static fn (object $record): bool => !$record instanceof OrderLine && !$record instanceof InvoiceLine || $record->line !== 1 : null)
            ->toolbarActions([
                BulkAction::make('read')->action(function (array $records): void {
                    $this->hookLog->record(sprintf('read %d records, %d distinct', count($records), count(array_unique(array_map(static fn (mixed $record): int => is_object($record) ? spl_object_id($record) : -1, $records)))));
                }),
                BulkAction::make('readKeys')->fetchSelectedRecords(false)->action(function (array $records): void {
                    $this->hookLog->record(sprintf('read %d keys, %d distinct', count($records), count(array_unique(array_map(static fn (mixed $key): string => is_string($key) ? $key : get_debug_type($key), $records)))));
                }),
                BulkAction::make('count')->action(function (BulkAction $action): void {
                    $this->hookLog->record(sprintf('queried %d rows', count($action->getSelectedRecordsQuery()->getQuery()->getArrayResult())));
                }),
            ]);
    }
}
