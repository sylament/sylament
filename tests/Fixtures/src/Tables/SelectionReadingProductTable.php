<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\BulkAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A product table whose toolbar actions read the selection and record what they read:
 * `archive` as a bulk action, `peek` as a plain action made one, `archiveByKey` by key and
 * `count` through the selection's query; `failOne`, `succeedOne` and `failAll` report their
 * counts to a title naming them; `tidy` and `tidyHalted` clear the selection afterwards, the
 * second after halting.
 */
#[AsLiveComponent(name: 'Tables:SelectionReadingProductTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class SelectionReadingProductTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $log,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('product')->from(Product::class, 'product'))
            ->columns([
                TextColumn::make('name'),
            ])
            ->defaultSort('name')
            ->toolbarActions([
                BulkAction::make('archive')->action($this->recordSelection(...)),
                Action::make('peek')->bulk()->accessSelectedRecords()->action($this->recordSelection(...)),
                BulkAction::make('archiveByKey')->fetchSelectedRecords(false)->action($this->recordSelection(...)),
                BulkAction::make('count')->action(function (BulkAction $action): void {
                    $this->log->record('count queried ' . count($action->getSelectedRecordsQuery()->getQuery()->getArrayResult()));
                }),
                $this->counted('failOne', static function (BulkAction $action, array $records): void {
                    $action->reportBulkProcessingFailure();
                }),
                $this->counted('succeedOne', static function (BulkAction $action, array $records): void {
                    $action->reportBulkProcessingSuccessfulRecordsCount(1);
                }),
                $this->counted('failAll', static function (BulkAction $action, array $records): void {
                    $action->reportCompleteBulkProcessingFailure();
                }),
                BulkAction::make('tidy')->deselectRecordsAfterCompletion()->action(static function (): void {
                }),
                BulkAction::make('tidyHalted')->deselectRecordsAfterCompletion()->action(static function (BulkAction $action): void {
                    $action->halt();
                }),
            ]);
    }

    /**
     * @param array<mixed> $records
     */
    private function recordSelection(Action $action, array $records): void
    {
        $this->log->record(sprintf('%s read %s', $action->getName(), implode(', ', array_map(
            static fn (mixed $record): string => $record instanceof Product ? $record->name : get_debug_type($record),
            $records,
        ))));
    }

    private function counted(string $name, Closure $report): BulkAction
    {
        return BulkAction::make($name)
            ->action($report)
            ->failureNotificationTitle(static fn (int $successCount, int $totalCount): string => "{$successCount} of {$totalCount} done");
    }
}
