<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;
use Sylament\Actions\BulkAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DeleteBulkAction;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Actions\RecordingDeleteBulkAction;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The bulk-delete host: a query-lane table over the container-wide entity
 * manager — the same reason
 * {@see DeletableSongsTable} uses one, since a confirmed bulk delete spans two
 * live requests — carrying `DeleteBulkAction` and five hand-written bulk actions
 * that exercise the counter reports, the query injections and the transaction
 * arms. The four flags are `LiveProp`s so a test can rebuild the same table
 * with the other arm armed, or with an override of the by-key delete in place,
 * across a round trip.
 */
#[AsLiveComponent(name: 'BulkDeleteSongsTable', template: '@TablesFixtures/components/BulkDeleteSongsTable.html.twig')]
class BulkDeleteSongsTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public bool $checksSelectable = false;

    #[LiveProp]
    public bool $fetchesRecords = true;

    #[LiveProp]
    public bool $usesTransaction = false;

    #[LiveProp]
    public ?string $deleteOverride = null;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        $table
            ->query($this->entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
            ->columns([TextColumn::make('title')->searchable()])
            ->toolbarActions([
                $this->makeDeleteAction()
                    ->fetchSelectedRecords($this->fetchesRecords)
                    ->databaseTransaction($this->usesTransaction),
                BulkAction::make('report-one')
                    ->failureNotificationTitle(fn (int $successCount, int $totalCount): string => sprintf('Archived %d of %d', $successCount, $totalCount))
                    ->action(function (BulkAction $action, array $records): void {
                        if ($records !== []) {
                            $action->reportBulkProcessingFailure();
                        }
                    }),
                BulkAction::make('report-all')
                    ->failureNotificationTitle(fn (int $totalCount, bool $isAll): string => $isAll
                        ? sprintf('Failed to archive %d records', $totalCount)
                        : 'Archived some')
                    ->action(function (BulkAction $action, array $records): void {
                        if ($records !== []) {
                            $action->reportCompleteBulkProcessingFailure();
                        }
                    }),
                BulkAction::make('inspect-query')
                    ->failureNotificationTitle(fn (int $successCount): string => sprintf('Both queries answered %d', $successCount))
                    ->action(function (BulkAction $action, QueryBuilder $selectedRecordsQuery, QueryBuilder $selection): void {
                        $byName = count($selectedRecordsQuery->getQuery()->getResult());
                        $byType = count($selection->getQuery()->getResult());

                        $action->reportBulkProcessingSuccessfulRecordsCount($byName === $byType ? $byName : -1);
                    }),
                BulkAction::make('boom')
                    ->databaseTransaction($this->usesTransaction)
                    ->action(function (array $records): void {
                        $this->removeSongs($records);

                        throw new RuntimeException('The bulk body blew up.');
                    }),
                BulkAction::make('give-up')
                    ->databaseTransaction($this->usesTransaction)
                    ->action(function (BulkAction $action, array $records): void {
                        $this->removeSongs($records);

                        $action->cancel(true);
                    }),
            ])
            ->paginated([10]);

        if ($this->checksSelectable) {
            $table->checkIfRecordIsSelectableUsing(fn (Song $record): bool => $record->getId() !== 3);
        }

        return $table;
    }

    /**
     * The plain delete, or {@see RecordingDeleteBulkAction} answering as the
     * test names.
     */
    private function makeDeleteAction(): DeleteBulkAction
    {
        return $this->deleteOverride === null
            ? DeleteBulkAction::make()
            : RecordingDeleteBulkAction::make()->answering($this->deleteOverride);
    }

    /**
     * @param array<mixed> $records
     */
    private function removeSongs(array $records): void
    {
        foreach ($records as $record) {
            if ($record instanceof Song) {
                $this->entityManager->remove($record);
            }
        }

        $this->entityManager->flush();
    }
}
